<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;

use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ProdukVarian;
use App\Models\Satuan;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use item;

class PenerimaanController extends Controller
{
    public function index(Request $request)
    {
        $pembelians = Pembelian::with(['items.details.satuan', 'items.varian.produk'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('nomer_pembelian', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return inertia('Grosir/Penerimaan/index', [
            'pembelians' => $pembelians,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }
    public function create(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $gudangId = 2;


        $varian = ProdukVarian::with([
            'produk.kategori',
            'produk.satuan',
            'details',
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->where('kode_warna', 'like', "%{$request->search}%")
                    ->orWhereHas('produk', function ($pq) use ($request) {
                        $pq->where('nama_produk', 'like', "%{$request->search}%");
                    });
            })
            ->paginate($perPage) // Tambahkan paginate() atau get() di sini
            ->withQueryString();


        return inertia('Grosir/Penerimaan/Create', [
            'nomer_pembelian' => 'INV' . date('mY') . 'SS-' . rand(100, 999),
            'produk' => ProdukVarian::orderBy('nama')->get(),
            'produk_by_color' => $varian, // Ini sekarang berisi objek LengthAwarePaginator
            'satuan' => Satuan::all(),
            'categories' => Kategori::all(),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'nomer_pembelian' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.varian_id' => 'required|exists:produk_varians,id',
            'items.*.details' => 'required|array|min:1',
            'items.*.details.*.panjang_isi' => 'required|numeric|min:0.1',
        ]);


        try {
            DB::transaction(function () use ($request) {
                // 1. Simpan Header Pembelian
                $pembelian = Pembelian::create([
                    'supplier_id' => 1,
                    'nomer' => $request->nomer_pembelian,
                    'tanggal' => $request->tanggal_transaksi,
                    'status' => 'DITERIMA',
                ]);

                Log::info('Membuat Pembelian: ' . $pembelian->nomer);

                foreach ($request->items as $item) {
                    $itemTotalRoll = count($item['details']);
                    $itemTotalQty = 0;

                    // 2. Simpan Item Pembelian
                    $pembelianItem = $pembelian->items()->create([
                        'varian_id' => $item['varian_id'],
                        'satuan_id' => $item['satuan_id'] ?? 1,
                        'total_qty' => 0, // Akan diupdate di bawah
                        'total_jumlah' => $itemTotalRoll,
                        'harga_beli' => 0,
                        'subtotal' => 0,
                    ]);

                    Log::info('  Menambahkan Item Pembelian untuk Varian ID: ' . $item['varian_id']);

                    $varian = ProdukVarian::findOrFail($item['varian_id']);

                    // 3. Buat Grup Stok (Batch)
                    $stok = $varian->stok()->create([
                        'gudang_id' => 2,
                        'nomor' => $request->nomer_pembelian . '-' . $item['varian_id'] . '-' . now()->format('YmdHis'),
                    ]);

                    Log::info('    Membuat Stok Grup dengan Nomor: ' . $stok->nomor);

                    foreach ($item['details'] as $detailData) {

                        // Simpan Detail Stok (Roll per Roll)
                        $pd_detail = $varian->details()->create([
                            'gudang_id' => 2,
                            'stok_id' => $stok->id,
                            'satuan_id' => $item['satuan_id'] ?? 1,
                            'qty' => $detailData['panjang_isi'],
                            'status' => 'tersedia',
                        ]);
                        // Simpan Detail Pembelian
                        $pembelianItem->details()->create([
                            'qty' => $detailData['panjang_isi'],
                            'satuan_id' => $item['satuan_id'] ?? 1,
                            'produk_detail_id' => $pd_detail->id,
                        ]);

                        Log::info('      Menambahkan Detail Stok dengan Panjang: ' . $detailData['panjang_isi']);

                        $itemTotalQty += $detailData['panjang_isi'];
                    }

                    // 4. Update Angka Akumulasi
                    $varian->increment('jumlah', $itemTotalRoll);
                    $varian->increment('panjang', $itemTotalQty);

                    $pembelianItem->update([
                        'total_qty' => $itemTotalQty,
                    ]);

                    Log::info('    Updated Varian ID ' . $varian->id . ' - Jumlah: ' . $varian->jumlah . ', Panjang: ' . $varian->panjang);
                }
            });

            return redirect()->route('grosir.penerimaan.index')
                ->with('message', 'Penerimaan barang berhasil disimpan!')
                ->with('type', 'success');
        } catch (\Exception $e) {
            Log::error("Gagal simpan pembelian: " . $e->getMessage());
            return back()->with('message', 'Gagal: ' . $e->getMessage())->with('type', 'error');
        }
    }

    public function edit($id, Request $request)
    {
        $perPage = $request->per_page ?? 10;

        // Ambil data pembelian dengan relasi lengkap
        $pembelian = Pembelian::with([
            'items.varian.produk',
            'items.details.satuan'
        ])->findOrFail($id);

        // Data Varian untuk Sidebar (Sama dengan Create)
        $varian = ProdukVarian::with(['produk.kategori', 'produk.satuan', 'details'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('kode_warna', 'like', "%{$request->search}%")
                    ->orWhereHas('produk', function ($pq) use ($request) {
                        $pq->where('nama_produk', 'like', "%{$request->search}%");
                    });
            })
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Grosir/Penerimaan/Edit', [
            'pembelian' => $pembelian,
            'produk_by_color' => $varian,
            'produk' => ProdukVarian::orderBy('nama')->get(),
            'satuan' => Satuan::all(),
            'categories' => Kategori::all(),
        ]);
    }

    public function update(Request $request, $id)
    {

        dd($request);

        $request->validate([
            'nomer_pembelian' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.varian_id' => 'required|exists:produk_varians,id',
            'items.*.details' => 'required|array|min:1',
            'items.*.details.*.panjang_isi' => 'required|numeric|min:0.1',
        ]);

        $pembelian = Pembelian::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $pembelian) {
                // Rollback data lama
                foreach ($pembelian->items as $item) {
                    $varian = $item->varian;
                    foreach ($item->details as $detail) {
                        $varian->details()
                            ->where('produk_detail_id', $detail->produk_detail_id)
                            ->delete();
                    }

                    // 1. Kurangi akumulasi stok di Varian
                    $varian->decrement('jumlah', $item->total_jumlah);
                    $varian->decrement('panjang', $item->total_qty);

                    $varian->stok()->delete();

                    // 2. Hapus detail pembelian (Rolls)
                    $item->details()->delete();

                    // 3. Hapus Item Pembelian itu sendiri
                    $item->delete();
                }

                foreach ($pembelian->items as $item) {
                    $varian = $item->varian;

                    foreach ($item->details as $detail) {
                        // Hapus detail stok terkait
                        $varian->details()
                            ->where('gudang_id', 2)
                            ->where('qty', $detail->qty)
                            ->where('status', 'tersedia')
                            ->limit(1)
                            ->delete();

                        // Hapus detail pembelian
                        $detail->delete();
                    }


                    $varian->decrement('jumlah', $item->total_jumlah);
                    $varian->decrement('panjang', $item->total_qty);

                    $item->delete();
                }


                // 1. Imput ulang pembelian
                Log::info('Membuat Pembelian: ' . $pembelian->nomer);

                foreach ($request->items as $item) {
                    $itemTotalRoll = count($item['details']);
                    $itemTotalQty = 0;

                    // 2. Simpan Item Pembelian
                    $pembelianItem = $pembelian->items()->create([
                        'varian_id' => $item['varian_id'],
                        'satuan_id' => $item['satuan_id'] ?? 1,
                        'total_qty' => 0, // Akan diupdate di bawah
                        'total_jumlah' => $itemTotalRoll,
                        'harga_beli' => 0,
                        'subtotal' => 0,
                    ]);

                    Log::info('  Menambahkan Item Pembelian untuk Varian ID: ' . $item['varian_id']);

                    $varian = ProdukVarian::findOrFail($item['varian_id']);

                    // 3. Buat Grup Stok (Batch)
                    $stok = $varian->stok()->create([
                        'gudang_id' => 2,
                        'nomor' => $request->nomer_pembelian . '-' . $item['varian_id'] . '-' . now()->format('YmdHis'),
                    ]);

                    Log::info('    Membuat Stok Grup dengan Nomor: ' . $stok->nomor);

                    foreach ($item['details'] as $detailData) {
                        // Simpan Detail Pembelian
                        $pembelianItem->details()->create([
                            'qty' => $detailData['panjang_isi'],
                            'satuan_id' => $item['satuan_id'] ?? 1,
                        ]);

                        // Simpan Detail Stok (Roll per Roll)
                        $varian->details()->create([
                            'gudang_id' => 2,
                            'stok_id' => $stok->id,
                            'satuan_id' => $item['satuan_id'] ?? 1,
                            'qty' => $detailData['panjang_isi'],
                            'status' => 'tersedia',
                        ]);

                        Log::info('      Menambahkan Detail Stok dengan Panjang: ' . $detailData['panjang_isi']);

                        $itemTotalQty += $detailData['panjang_isi'];
                    }

                    // 4. Update Angka Akumulasi
                    $varian->increment('jumlah', $itemTotalRoll);
                    $varian->increment('panjang', $itemTotalQty);

                    $pembelianItem->update([
                        'total_qty' => $itemTotalQty,
                    ]);

                    Log::info('    Updated Varian ID ' . $varian->id . ' - Jumlah: ' . $varian->jumlah . ', Panjang: ' . $varian->panjang);
                }
            });

            return redirect()->route('grosir.penerimaan.index')->with('message', 'Nota berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('message', 'Gagal: ' . $e->getMessage());
        }
    }



    public function updateData()
    {


        $products = DB::connection('sqlite_lama')->table('products')->get();

        foreach ($products as $oldProduct) {

            $prdId = $oldProduct->id;
            $namaProdukClean = trim(ucwords(strtolower($oldProduct->name)));

            $kategori = Kategori::firstOrCreate(
                ['slug' => Str::slug($namaProdukClean)],
                ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
            );

            $kategoriId = $kategori->id;

            $satuankecil = trim(ucwords(strtolower($oldProduct->unit_satuan)));
            $satuanId = null;
            if ($satuankecil === 'yard') {
                $satuanId = 1;
            } elseif ($satuankecil === 'meter') {
                $satuanId = 2;
            } else {
                $satuanId = 3;
            }

            $produk = Produk::updateOrInsert([
                'id' => $prdId,
                'nama_produk' => $oldProduct->name,
                'kategori_id' => $kategoriId,
                'satuan_id' => $satuanId,
                'brand_id' => null,
                'deskripsi' => '',
                'created_at' => $oldProduct->created_at ?? now(),
                'updated_at' => $oldProduct->updated_at ?? now(),
            ]);

            $varian = ProdukVarian::insert([
                'id' => $prdId,
                'produk_id' => $prdId,
                'gudang_id' => 1,
                'nama_varian' => $oldProduct->kode_warna,
                'tipe' => $oldProduct->tipe,
                'lebar' => $oldProduct->lebar_kain ?? 0,
                'tinggi' => 0,
                'tebal' => 0,
                'sku' => '',
                'is_paket' => false,
                'harga_beli' => 0,
                'harga_jual' => 0,
                'created_at' => $oldProduct->created_at ?? now(),
                'updated_at' => $oldProduct->updated_at ?? now(),
            ]);
        }

        // $invoice = DB::connection('sqlite_lama')->table('products')->get();

        // foreach ($invoice as $inv) {

        //     $pembelianId = DB::table('pembelians')->insertGetId([
        //         'supplier_id'       => $inv->supplier_id,
        //         'nomer_pembelian'   => $inv->supplier_invoice_no,
        //         'tanggal_transaksi' => $inv->transaction_date,
        //         'status'            => 'DITERIMA',
        //         'total_jumlah_qty'        => $inv->total_purchase_roll ?? 0,
        //         'total_jumlah_panjang'     => $inv->total_purchase_length ?? 0,
        //         'created_at'        => $inv->created_at ?? now(),
        //         'updated_at'        => $inv->updated_at ?? now(),
        //     ]);

        //     // 2. Ambil Item Invoice dari SQLite
        //     $oldItems = DB::connection('sqlite_lama')->table('purchase_invoice_items')
        //         ->where('purchase_invoice_id', $inv->id)->get();

        //     foreach ($oldItems as $item) {
        //         // 3. Ambil Rincian Roll untuk Item ini
        //         $oldRolls = DB::connection('sqlite_lama')->table('jumlah_rolls')
        //             ->where('purchase_invoice_item_id', $item->id)->get();

        //         foreach ($oldRolls as $roll) {
        //             // --- DISINI KUNCI PERUBAHAN LEVELNYA ---

        //             $oldProduct = DB::connection('sqlite_lama')->table('products')
        //                 ->find($roll->product_id);

        //             if (!$oldProduct) continue;

        //             // --- LOGIKA KATEGORI & WARNA ---
        //             $namaClean = trim(ucwords(strtolower($oldProduct->name)));

        //             // 1. Cari/Buat Kategori (Nama Kain)
        //             $kategori = \App\Models\Kategori::firstOrCreate(
        //                 ['nama_kategori' => $namaClean, 'status' => 'nama'],
        //                 ['slug' => \Illuminate\Support\Str::slug($namaClean)]
        //             );

        //             // A. Buat Produk baru (sebagai Identitas Roll fisik)
        //             $produkId = DB::table('produks')->insertGetId([]);

        //             // B. Buat Pembelian Item (Satu baris per satu produk/roll)
        //             // Jadi jika di invoice lama ada 10 roll, di tabel ini akan muncul 10 baris
        //             DB::table('pembelian_items')->insert([
        //                 'pembelian_id'    => $pembelianId,
        //                 'produk_id'       => $produkId,
        //                 'panjang_perroll' => $roll->panjang,
        //                 'jumlah_roll'     => 1,
        //                 'created_at'      => $oldProduct->created_at,
        //                 'updated_at'      => $oldProduct->created_at,
        //             ]);
        //         }
        //     }
        // };

        return inertia('Grosir/Penerimaan/index');
    }
}
