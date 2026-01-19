<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\DetailPaket;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ProdukVarian;
use App\Models\StokFisik;
use function PHPUnit\Framework\isNull;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PenerimaanController extends Controller
{
    public function index(Request $request)
    {
        $pembelians = Pembelian::with(['items.details', 'items.varian.produk'])
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
        $gudangId = 1;

        $products = ProdukVarian::with([
            'produk.kategori',
            'produk.satuan',
            'stokFisiks' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)->with('detailPakets');
            }
        ])
            ->withSum(['stokFisiks as total_qty' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            }], 'total_qty')
            ->withSum(['stokFisiks as total_panjang' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            }], 'total_panjang')
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
            'produk_by_color' => $products, // Ini sekarang berisi objek LengthAwarePaginator
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomer_pembelian' => 'required|string|unique:pembelians,nomer_pembelian',
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
                    'nomer_pembelian' => $request->nomer_pembelian,
                    'tanggal_transaksi' => $request->tanggal_transaksi,
                    'status' => 'DITERIMA',
                    'total_jumlah_qty' => 0,
                    'total_jumlah_panjang' => 0,
                ]);

                $grandTotalRoll = 0;
                $grandTotalPanjang = 0;

                foreach ($request->items as $item) {
                    $itemTotalPanjang = 0;
                    $itemTotalRoll = count($item['details']);

                    $pembelianItem = $pembelian->items()->create([
                        'varian_id' => $item['varian_id'],
                        'total_qty' => $itemTotalRoll,
                        'total_panjang' => 0,
                    ]);

                    // Perbaikan 1: Gunakan is_null() atau == null
                    $stok = StokFisik::where('varian_id', $item['varian_id'])->first();
                    if (is_null($stok)) {
                        $stok = StokFisik::create([
                            'varian_id' => $item['varian_id'],
                            'gudang_id' => 1,
                            'total_qty' => $itemTotalRoll,
                            'total_panjang' => 0,
                            'harga_jual' => 0,
                            'harga_beli' => 0,
                        ]);
                    } else {
                        // Jika stok sudah ada, tambahkan qty rollnya
                        $stok->increment('total_qty', $itemTotalRoll);
                    }

                    foreach ($item['details'] as $detail) {
                        $panjang = floatval($detail['panjang_isi']);
                        $itemTotalPanjang += $panjang;

                        // Pastikan nama kolom di migration sesuai (tadi di migration Anda 'panjang_isi', di sini 'qty'?)
                        // Saya sesuaikan dengan snippet migration Anda sebelumnya: 'panjang_isi'
                        $pembelianItem->details()->create([
                            'qty' => $panjang,
                            'lot_number' => $detail['lot_number'] ?? null,
                        ]);

                        DetailPaket::create([
                            'induk_varian_id' => $item['varian_id'],
                            'anak_varian_id' => $stok->id,
                            'qty' => $panjang,
                        ]);
                    }

                    // Update total per item varian
                    $pembelianItem->update(['total_panjang' => $itemTotalPanjang]);

                    // Perbaikan 2: Sintaks increment yang benar (kolom, nilai)
                    $stok->increment('total_panjang', $itemTotalPanjang);

                    $varian = ProdukVarian::find($item['varian_id']);
                    $varian->increment('panjang', $itemTotalPanjang);

                    $grandTotalRoll += $itemTotalRoll;
                    $grandTotalPanjang += $itemTotalPanjang;
                }

                // Update Header Akhir
                $pembelian->update([
                    'total_jumlah_panjang' => $grandTotalPanjang,
                    'total_jumlah_qty' => $grandTotalRoll,
                ]);
            });

            return redirect()->route('grosir.penerimaan.index')
                ->with('message', 'Penerimaan barang berhasil disimpan!')
                ->with('type', 'success');
        } catch (\Exception $e) {
            // Log error asli agar Anda bisa lihat di storage/logs/laravel.log jika gagal lagi
            Log::error("Gagal simpan pembelian: " . $e->getMessage());

            return back()->with('message', 'Gagal: ' . $e->getMessage())->with('type', 'error');
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

            $stok = StokFisik::insert([
                'varian_id' => $prdId,
                'gudang_id' => 1,
                'nomor_batch' => '',
                'tanggal_kadaluarsa' => null,
                'total_panjang' => $oldProduct->total_lenght,
                'total_qty' => $oldProduct->total_roll,
                'harga_jual' => 0,
                'harga_beli' => 0,
                'created_at' => $oldProduct->created_at ?? now(),
                'updated_at' => $oldProduct->updated_at ?? now(),
            ]);

            $rolls = DB::connection('sqlite_lama')->table('jumlah_roll')->where('product_id', $prdId)->get();
            foreach ($rolls as $roll) {
                $detailPaket = DetailPaket::insert([
                    'id' => $roll->id,
                    'induk_varian_id' => $prdId,
                    'anak_varian_id' => $stok->id,
                    'satuan_id' => $satuanId,
                    'qty' => $roll->panjang,
                    'isi_2' => 0,
                    'status' => $roll->status,
                    'created_at' => $oldProduct->created_at ?? now(),
                    'updated_at' => $oldProduct->updated_at ?? now(),
                ]);
            }
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
