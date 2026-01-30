<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\PenjualanItem;
use App\Models\Produk;
use App\Models\ProdukDetail;
use App\Models\ProdukVarian;
use App\Models\Stok;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\PrinterService;

class PenjualanGrosirController extends Controller
{
    public function searchProduk(Request $request)
    {
        $search = $request->q;

        $data = ProdukVarian::with([
            'produk:id,nama',
            'details' => function ($query) {
                $query->where('gudang_id', 2)
                    ->where('status', 'tersedia')
                    ->with('satuan:id,inisial');
            }
        ])
            ->whereHas('stok', function ($qr) {
                $qr->where('gudang_id', 2)->where('total_qty', '>', 0);
            })
            ->where(function ($query) use ($search) {
                $query->where('nama', 'like', "%$search%")
                    ->orWhereHas('produk', function ($q) use ($search) {
                        $q->where('nama', 'like', "%$search%");
                    });
            })
            ->limit(10) // Ambil 10-20 saja, biar instan
            ->get();

        return response()->json($data);
    }
    public function index(Request $request)
    {
        $penjualan = Penjualan::with(['items.details.satuan', 'items.varian.produk', 'pelanggan', 'pembayaran',])
            ->where('bagian', 'grosir')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    // Cari berdasarkan nomor nota penjualan
                    $query->where('nomer', 'like', "%{$request->search}%")
                        // ATAU cari berdasarkan nama di tabel pelanggan
                        ->orWhereHas('pelanggan', function ($subQuery) use ($request) {
                            $subQuery->where('nama', 'like', "%{$request->search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return inertia('Grosir/Penjualan/Transaksi', [
            'penjualan' => $penjualan,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create(Request $request)
    {
        $no_nota = '';
        $createNo = Penjualan::orderBy('id', 'desc')->latest()->first();

        if ($createNo) {
            $nomernya = $createNo->id + 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        } else {
            $nomernya = 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        }

        $perPage = 30;

        $gudangId = 2;

        $varian = ProdukVarian::with([
            'produk.satuan',
            'produk.kategori',
            'details' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)->where('status', 'tersedia');
            },
            'stok' => function ($qy) {
                $qy->where('gudang_id', 2);
            }
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhereHas('produk', function ($query) use ($request) {
                        $query->where('nama', 'like', "%{$request->search}%");
                    });
            })
            ->when($request->kategori, function ($q) use ($request) {
                // Pastikan filter kategori mencari ke tabel produk jika kategori_id ada di sana
                $q->whereHas('produk', function ($query) use ($request) {
                    $query->where('kategori_id', $request->kategori);
                });
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Grosir/Penjualan/Create', [
            'no_nota' => $no_nota,
            'produk_varian' => $varian, // Mengirim data sesuai limit
            'customers_data' => Pelanggan::select('id', 'nama')->get(),
            'suppliers' => Supplier::select('id', 'nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:50'],
            'transaction_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'marketing_name' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:produk_varians,id'],
            'items.*.total_qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.rolls' => ['required', 'array', 'min:1'],
            'items.*.rolls.*.roll_id' => ['required', 'integer', 'exists:produk_details,id'],
            'items.*.rolls.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.rolls.*.satuan_id' => ['required', 'integer', 'exists:satuans,id'],
        ]);

        // Validasi Logis
        foreach ($validated['items'] as $item) {
            if ((float) $item['subtotal'] !== (float) ($item['total_qty'] * $item['unit_price'])) {
                abort(422, 'Subtotal tidak valid pada produk ' . ($item['product_name'] ?? ''));
            }
            if ((float) collect($item['rolls'])->sum('qty') !== (float) $item['total_qty']) {
                abort(422, 'Total qty roll tidak sinkron pada produk ' . ($item['product_name'] ?? ''));
            }
        }

        return DB::transaction(function () use ($validated) {
            $pelanggan = Pelanggan::updateOrCreate(
                ['nama' => $validated['customer_name']],
                ['slug' => Str::slug($validated['customer_name'])],
            );

            $marketing = Supplier::updateOrCreate(
                ['nama' => $validated['marketing_name']],
                ['slug' => Str::slug($validated['marketing_name'])],
            );

            $penjualan = Penjualan::create([
                'nomer' => $validated['invoice_no'],
                'tanggal' => $validated['transaction_date'],
                'bagian' => 'grosir',
                'pelanggan_id' => $pelanggan->id,
                'marketing_id' => $marketing->id,
                'total_penjualan' => 0, // Akan diupdate di bawah
                'status' => 'UNPAID',
            ]);

            $grandTotal = 0;

            foreach ($validated['items'] as $item) {
                $produkVarian = ProdukVarian::with('produk')->find($item['product_id']);
                $jumlahRollItem = count($item['rolls']);

                $itemPenjualan = $penjualan->items()->create([
                    'varian_id' => $produkVarian->id,
                    'satuan_id' => $produkVarian->produk->satuan_id,
                    'total_qty' => $item['total_qty'],
                    'total_jumlah' => $jumlahRollItem,
                    'harga_satuan' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $grandTotal += $item['subtotal'];
                $totalQtyItem = 0;

                foreach ($item['rolls'] as $roll) {
                    $itemPenjualan->details()->create([
                        'produk_detail_id' => $roll['roll_id'],
                        'satuan_id' => $roll['satuan_id'],
                        'qty' => $roll['qty'],
                        'harga' => $item['unit_price'],
                        'jumlah_harga' => $roll['qty'] * $item['unit_price'],
                        'status' => 'prepare',
                    ]);

                    // Update status roll menjadi terjual (Gunakan model yang tepat, misal: ProdukDetail)
                    // Pastikan model ProdukDetail yang di-update, bukan PenjualanDetail
                    // 1. Cari modelnya dulu
                    $produkDetail = ProdukDetail::find($roll['roll_id']); // Pastikan ini model

                    if ($produkDetail) {
                        // 2. Lakukan update pada instance model
                        $produkDetail->update([
                            'status' => 'terjual',
                            'gudang_id' => 55
                        ]);

                        // 3. Panggil relasi kartuStoks (Sekarang tidak akan error)
                        $produkDetail->kartuStoks()->create([
                            'varian_id'      => $produkDetail->varian_id,
                            'dari_gudang_id' => 2,
                            'ke_gudang_id'   => 0,
                            'tipe'           => 'keluar',
                            'qty'            => $produkDetail->qty,
                            'stok_awal'      => $produkDetail->qty, // Sesuaikan logik stok awal Anda
                            'stok_akhir'     => 0,                  // Karena barang keluar, stok akhir jadi 0
                            'keterangan'     => 'Keluar Di Penjualan No. ' . ($no_nota ?? '-'),
                            'user_id'        => Auth::id(),
                        ]);
                    }

                    $totalQtyItem += $roll['qty'];
                }

                // Update stok di level varian
                $produkVarian->stok()->decrement('total_qty', $totalQtyItem);
                $produkVarian->stok()->decrement('total_unit', $jumlahRollItem);
            }

            // Update total keseluruhan
            $penjualan->update(['total_penjualan' => $grandTotal]);

            return redirect()
                ->route('grosir.penjualan.index')
                ->with('success', 'Transaksi berhasil disimpan');
        });
    }

    public function show($id)
    {
        $penjualan = Penjualan::with([
            'items.varian.produk',
            'items.details.satuan',
            'pelanggan',
            'marketing',
            'pembayaran.retur',
            'pembayaran.details'
        ])
            ->where('bagian', 'grosir')
            ->findOrFail($id);

        return inertia('Grosir/Penjualan/Show', [
            'penjualan' => $penjualan,
        ]);
    }

    public function printNota($id, PrinterService $printer)
    {
        // Ambil data lengkap dengan relasinya agar tidak error di printer
        $penjualan = Penjualan::with([
            'items.varian.produk',
            'items.details.satuan',
            'pelanggan',
            'marketing',
            'pembayaran.retur',
            'pembayaran.details'
        ])
            ->where('bagian', 'grosir')
            ->findOrFail($id);

        try {
            // Panggil fungsi cetak dari service
            $printer->cetakNota($penjualan);

            return response()->json(['status' => 'success', 'message' => 'Nota sedang dicetak']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $no_nota = '';
        $createNo = Penjualan::orderBy('id', 'desc')->latest()->first();

        if ($createNo) {
            $nomernya = $createNo->id + 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        } else {
            $nomernya = 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        }


        $penjualan = Penjualan::with(['items.varian.produk', 'items.details.satuan', 'pelanggan', 'marketing'])->where('bagian', 'grosir')
            ->findOrFail($id);

        // Ambil produk varian yang sedang tersedia + produk yang sedang ada di invoice ini 
        // agar kalau mau edit, barang yang sudah 'terjual' di invoice tersebut tetap muncul di pilihan
        $varian = ProdukVarian::with([
            'produk',
            'details' => function ($query) {

                $query->where('status', 'tersedia')->where('gudang_id', 2)->with('satuan');
            }
        ])->where('gudang_id', 2)->get();
        // dd($penjualan);
        return inertia('Grosir/Penjualan/Edit', [ // Gunakan file yang sama
            'no_nota' => $penjualan->nomer,
            'produk_varian' => $varian,
            'penjualan' => $penjualan,
            'customers_data' => Pelanggan::all()
        ]);
    }

    public function update(Request $request, $id)
    {
        // Ambil data penjualan lama beserta item dan detail roll-nya
        $penjualan = Penjualan::with('items.details')->findOrFail($id);

        // Validasi tetap diperlukan
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string'],
            'marketing_name' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:produk_varians,id'],
            'items.*.total_qty' => ['required', 'numeric'],
            'items.*.unit_price' => ['required', 'numeric'],
            'items.*.subtotal' => ['required', 'numeric'],
            'items.*.rolls' => ['required', 'array'],
        ]);

        // dd($request);
        return DB::transaction(function () use ($validated, $penjualan) {

            $pelanggan = Pelanggan::updateOrCreate(
                ['slug' => Str::slug($validated['customer_name'])],
                ['nama' => $validated['customer_name']]
            );

            $marketing = Supplier::updateOrCreate(
                ['slug' => Str::slug($validated['marketing_name'])],
                ['nama' => $validated['marketing_name']]
            );
            // --- STEP 1: KEMBALIKAN STOK LAMA ---
            foreach ($penjualan->items as $itemLama) {
                $qtyTotalKembali = 0;
                $jumlahRollKembali = count($itemLama->details);

                foreach ($itemLama->details as $det) {
                    // Set status roll produk kembali menjadi 'tersedia'
                    $produkDetail = ProdukDetail::find($det->produk_detail_id);

                    if ($produkDetail) {
                        $produkDetail->update(['status' => 'tersedia', 'gudang_id' => 2]);
                        $produkDetail->kartuStoks()->delete();
                    }

                    $qtyTotalKembali += $det->qty;
                }

                // Tambahkan kembali stok ke tabel ProdukVarian
                $varian = ProdukVarian::find($itemLama->varian_id);
                if ($varian) {
                    $varian->stock()->increment('total_qty', $qtyTotalKembali);
                    $varian->stock()->increment('total_unit', $jumlahRollKembali);
                }
            }

            // --- STEP 2: BERSIHKAN DATA ITEM LAMA ---
            // Kita hapus item & detail penjualan agar bisa diganti yang baru
            $penjualan->items()->each(fn($i) => $i->details()->delete());
            $penjualan->items()->delete();

            // --- STEP 3: PROSES DATA BARU (POTONG STOK LAGI) ---
            $totalAkhir = 0;
            foreach ($validated['items'] as $item) {
                $v = ProdukVarian::with('produk')->find($item['product_id']);

                $newItem = $penjualan->items()->create([
                    'varian_id' => $v->id,
                    'satuan_id' => $v->produk->satuan_id,
                    'total_qty' => $item['total_qty'],
                    'total_jumlah' => count($item['rolls']),
                    'harga_satuan' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $totalAkhir += $item['subtotal'];

                foreach ($item['rolls'] as $r) {
                    $newItem->details()->create([
                        'produk_detail_id' => $r['roll_id'],
                        'satuan_id' => $r['satuan_id'],
                        'qty' => $r['qty'],
                        'harga' => $item['unit_price'],
                        'jumlah_harga' => $r['qty'] * $item['unit_price'],
                        'status' => 'prepare',
                    ]);
                    // Tandai roll menjadi terjual kembali
                    $produkDetail = ProdukDetail::find($r['roll_id']); // Pastikan ini model

                    if ($produkDetail) {
                        // 2. Lakukan update pada instance model
                        $produkDetail->update(['status' => 'terjual', 'gudang_id' => 55]);

                        // 3. Panggil relasi kartuStoks (Sekarang tidak akan error)
                        $produkDetail->kartuStoks()->updateOrCreate([
                            'varian_id'      => $produkDetail->varian_id,
                            'dari_gudang_id' => 2,
                            'ke_gudang_id'   => 0,
                            'tipe'           => 'keluar',
                            'qty'            => $produkDetail->qty,
                            'stok_awal'      => $produkDetail->qty, // Sesuaikan logik stok awal Anda
                            'stok_akhir'     => 0,                  // Karena barang keluar, stok akhir jadi 0
                            'keterangan'     => 'Keluar Di Penjualan No. ' . ($no_nota ?? '-'),
                            'user_id'        => Auth::id(),
                        ]);
                    }
                }

                // Potong stok varian
                $v->stok()->decrement('total_qty', $item['total_qty']);
                $v->stok()->decrement('total_unit', count($item['rolls']));
            }

            // --- STEP 4: UPDATE DATA INDUK ---
            $penjualan->update([
                'pelanggan_id' => $pelanggan->id,
                'marketing_id' => $marketing->id,
                'tanggal' => $validated['transaction_date'],
                'total_penjualan' => $totalAkhir,
                'status' => 'UNPAID', // atau biarkan sesuai status lama
            ]);

            return redirect()->route('grosir.penjualan.index')->with('success', 'Update Berhasil');
        });
    }

    public function destroy($id)
    {
        // Ambil data penjualan beserta item dan detail roll-nya
        $penjualan = Penjualan::with('items.details')->findOrFail($id);

        return DB::transaction(function () use ($penjualan) {

            // --- STEP 1: KEMBALIKAN STOK (ROLLBACK) ---
            foreach ($penjualan->items as $item) {
                $qtyKembali = 0;
                $rollKembali = count($item->details);

                foreach ($item->details as $detail) {

                    $produkDetail = ProdukDetail::find($detail->produk_detail_id);

                    if ($produkDetail) {
                        $produkDetail->update(['status' => 'tersedia', 'gudang_id', 2]);
                        $produkDetail->kartuStoks()->delete();
                    }

                    $qtyKembali += $detail->qty;
                }

                // Tambahkan kembali stok ke Produk Varian
                $varian = ProdukVarian::find($item->varian_id);
                if ($varian) {
                    $varian->stok()->increment('total_qty', $qtyKembali);
                    $varian->stok()->increment('total_unit', $rollKembali);
                }
            }

            // --- STEP 2: HAPUS DATA ---
            // Jika kamu pakai onDelete('cascade') di database, cukup hapus $penjualan.
            // Jika tidak, hapus manual detailnya dulu.
            $penjualan->items()->each(function ($item) {
                $item->details()->delete();
            });
            $penjualan->items()->delete();
            $penjualan->delete();

            return redirect()->back()->with('success', 'Nota berhasil dihapus dan stok telah dikembalikan.');
        });
    }

    public function updateData()
    {
        DB::transaction(function () {
            $oldSale = DB::connection('sqlite_lama')->table('sales')->get();

            foreach ($oldSale as $sale) {
                $oldCustomer = DB::connection('sqlite_lama')->table('customers')
                    ->where('id', $sale->customer_id)->first();

                if (!$oldCustomer) continue;

                // 1. Pelanggan (Cari berdasarkan nama, update sisanya)
                $pelanggan = Pelanggan::updateOrCreate(
                    ['nama' => $oldCustomer->name],
                    [
                        'phone' => $oldCustomer->phone,
                        'email' => $oldCustomer->email,
                        'alamat' => $oldCustomer->address,
                        'slug' => Str::slug($oldCustomer->name),
                        'status' => $oldCustomer->status ?? 'aktif',
                    ]
                );

                // 2. Marketing (Simpan di tabel Supplier/Marketing sesuai strukturmu)
                $marketing = Supplier::updateOrCreate(
                    ['nama' => $oldCustomer->pnj],
                    [
                        'slug' => Str::slug($oldCustomer->pnj),
                        'status' => 'marketing',
                    ]
                );

                // 3. Header Penjualan (Gunakan updateOrCreate agar tidak duplikat saat dijalankan ulang)
                $newSale = Penjualan::updateOrCreate(
                    ['nomer' => $sale->invoice_no],
                    [
                        'tanggal' => $sale->transaction_date,
                        'bagian' => 'grosir',
                        'pelanggan_id' => $pelanggan->id,
                        'marketing_id' => $marketing->id,
                        'total_penjualan' => $sale->total_amount,
                        'diskon' => $sale->discount_amount,
                        'ongkir' => $sale->ongkir ?? 0,
                        'status' => strtoupper($sale->status),
                    ]
                );

                $oldSaleItems = DB::connection('sqlite_lama')->table('sale_items')
                    ->where('sale_id', $sale->id)->get();

                foreach ($oldSaleItems as $item) {
                    // Cari varian di DB baru berdasarkan ID produk lama
                    $varian = ProdukVarian::find($item->product_id);
                    if (!$varian) continue;

                    $newSaleItem = $newSale->items()->updateOrCreate(
                        ['varian_id' => $varian->id, 'penjualan_id' => $newSale->id],
                        [
                            'satuan_id' => $varian->produk->satuan_id ?? 1,
                            'total_qty' => $item->length_out,
                            'total_jumlah' => $item->roll_out,
                            'harga_satuan' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ]
                    );

                    // Ambil detail roll dari data lama
                    $oldItemDetail = DB::connection('sqlite_lama')->table('jumlah_rolls')
                        ->where('sale_item_id', $item->id)->get();

                    foreach ($oldItemDetail as $det) {

                        $produkDetail = ProdukDetail::where('old_id', $det->sale_item_id)->first();

                        if ($produkDetail) {
                            // Buat detail penjualan
                            $newSaleItem->details()->create([
                                'produk_detail_id' => $produkDetail->id,
                                'satuan_id' => $newSaleItem->satuan_id,
                                'qty' => $det->panjang,
                                'harga' => $newSaleItem->harga_satuan,
                                'jumlah_harga' => $det->panjang * $newSaleItem->harga_satuan,
                            ]);

                            // UPDATE STOK: Ubah status menjadi terjual
                            $produkDetail->update(['status' => 'terjual']);

                            Log::info('update :' . $produkDetail->id . 'terjual');

                            $produkDetail->kartuStoks()->create([
                                'varian_id'      => $produkDetail->varian_id,
                                'dari_gudang_id' => 2,
                                'ke_gudang_id'   => 0,
                                'tipe'           => 'keluar',
                                'qty'            => $det->panjang,
                                'stok_awal'      => $det->panjang,
                                'stok_akhir'     => $det->panjang,
                                'keterangan'     => 'keluar Di Penjualan No. ' . $newSale->nomer,
                                'user_id'        => Auth::id(),
                            ]);
                        }
                    }
                    $stok = Stok::where('varian_id', $item->product_id);
                    // KURANGI STOK AKUMULASI DI VARIAN
                    $stok->decrement('total_unit', $item->roll_out);
                    $stok->decrement('total_qty', $item->length_out);
                }
            }
        });

        return back()->with('message', 'Refactoring Penjualan Selesai!');
    }
}
