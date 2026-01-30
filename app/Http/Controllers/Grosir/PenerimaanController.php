<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;

use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\ProdukVarian;

use App\Models\Satuan;

use function Symfony\Component\Clock\now;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use item;

class PenerimaanController extends Controller
{
    public function index(Request $request)
    {
        $pembelians = Pembelian::with([
            "items.details.satuan",
            "items.varian.produk",
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->where("nomer_pembelian", "like", "%{$request->search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return inertia("Grosir/Penerimaan/index", [
            "pembelians" => $pembelians,
            "filters" => $request->only(["search", "per_page"]),
        ]);
    }
    public function create(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $gudangId = 2;

        $varian = ProdukVarian::with([
            "produk.kategori",
            "produk.satuan",
            "details",
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(
                    "kode_warna",
                    "like",
                    "%{$request->search}%",
                )->orWhereHas("produk", function ($pq) use ($request) {
                    $pq->where("nama_produk", "like", "%{$request->search}%");
                });
            })
            ->paginate($perPage) // Tambahkan paginate() atau get() di sini
            ->withQueryString();

        return inertia("Grosir/Penerimaan/Create", [
            "nomer_pembelian" => (string) Str::ulid(),
            "produk" => ProdukVarian::orderBy("nama")->get(),
            "produk_by_color" => $varian, // Ini sekarang berisi objek LengthAwarePaginator
            "satuan" => Satuan::all(),
            "categories" => Kategori::all(),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            "nomer_pembelian" => "required|string",
            "tanggal_transaksi" => "required|date",
            "items" => "required|array|min:1",
            "items.*.varian_id" => "required|exists:produk_varians,id",
            "items.*.details" => "required|array|min:1",
            "items.*.details.*.panjang_isi" => "required|numeric|min:0.1",
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. Simpan Header Pembelian
                $pembelian = Pembelian::create([
                    "supplier_id" => 1,
                    "nomer" => $request->nomer_pembelian,
                    "tanggal" => $request->tanggal_transaksi,
                    "status" => "DITERIMA",
                ]);

                Log::info("Membuat Pembelian: " . $pembelian->nomer);

                foreach ($request->items as $item) {
                    $itemTotalRoll = count($item["details"]);
                    $itemTotalQty = 0;

                    // 2. Simpan Item Pembelian
                    $pembelianItem = $pembelian->items()->create([
                        "varian_id" => $item["varian_id"],
                        "satuan_id" => $item["satuan_id"] ?? 1,
                        "total_qty" => 0, // Akan diupdate di bawah
                        "total_jumlah" => $itemTotalRoll,
                        "harga_beli" => 0,
                        "subtotal" => 0,
                    ]);

                    Log::info(
                        "  Menambahkan Item Pembelian untuk Varian ID: " .
                            $item["varian_id"],
                    );

                    $varian = ProdukVarian::findOrFail($item["varian_id"]);

                    // 3. Buat Grup Stok (Batch)
                    $stok = $varian->stok()->create([
                        "gudang_id" => 2,
                        "nomer" =>
                            $request->nomer_pembelian .
                            "-" .
                            $item["varian_id"] .
                            "-" .
                            now()->format("YmdHis"),
                    ]);

                    Log::info(
                        "    Membuat Stok Grup dengan Nomor: " . $stok->nomor,
                    );

                    foreach ($item["details"] as $detailData) {
                        // Simpan Detail Stok (Roll per Roll)
                        $pd_detail = $varian->details()->create([
                            "gudang_id" => 2,
                            "stok_id" => $stok->id,
                            "satuan_id" => $item["satuan_id"] ?? 1,
                            "qty" => $detailData["panjang_isi"],
                            "status" => "tersedia",
                        ]);
                        // Simpan Detail Pembelian
                        $pembelianItem->details()->create([
                            "qty" => $detailData["panjang_isi"],
                            "satuan_id" => $item["satuan_id"] ?? 1,
                            "produk_detail_id" => $pd_detail->id,
                        ]);

                        Log::info(
                            "      Menambahkan Detail Stok dengan Panjang: " .
                                $detailData["panjang_isi"],
                        );

                        $itemTotalQty += $detailData["panjang_isi"];
                    }

                    // 4. Update Angka Akumulasi
                    $stok->increment("total_unit", $itemTotalRoll);
                    $stok->increment("total_qty", $itemTotalQty);

                    $pembelianItem->update([
                        "total_qty" => $itemTotalQty,
                    ]);
                }
            });

            return redirect()
                ->route("grosir.penerimaan.index")
                ->with("message", "Penerimaan barang berhasil disimpan!")
                ->with("type", "success");
        } catch (\Exception $e) {
            Log::error("Gagal simpan pembelian: " . $e->getMessage());
            return back()
                ->with("message", "Gagal: " . $e->getMessage())
                ->with("type", "error");
        }
    }

    public function edit($id, Request $request)
    {
        $perPage = $request->per_page ?? 10;

        // Ambil data pembelian dengan relasi lengkap
        $pembelian = Pembelian::with([
            "items.varian.produk",
            "items.details.satuan",
        ])->findOrFail($id);

        // Data Varian untuk Sidebar (Sama dengan Create)
        $varian = ProdukVarian::with([
            "produk.kategori",
            "produk.satuan",
            "details" => function ($kueri) {
                $kueri->where("gudang_id", 2);
            },
        ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(
                    "kode_warna",
                    "like",
                    "%{$request->search}%",
                )->orWhereHas("produk", function ($pq) use ($request) {
                    $pq->where("nama_produk", "like", "%{$request->search}%");
                });
            })
            ->paginate($perPage)
            ->withQueryString();

        return inertia("Grosir/Penerimaan/Edit", [
            "pembelian" => $pembelian,
            "produk_by_color" => $varian,
            "produk" => ProdukVarian::orderBy("nama")->get(),
            "satuan" => Satuan::all(),
            "categories" => Kategori::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            "nomer_pembelian" => "required|string",
            "tanggal_transaksi" => "required|date",
            "items" => "required|array|min:1",
            "items.*.varian_id" => "required|exists:produk_varians,id",
            "items.*.details" => "required|array|min:1",
            "items.*.details.*.panjang_isi" => "required|numeric|min:0.1",
        ]);

        $pembelian = Pembelian::with("items.details")->findOrFail($id);

        try {
            DB::transaction(function () use ($request, $pembelian) {
                // 1. Update Header Pembelian
                $pembelian->update([
                    "nomer" => $request->nomer_pembelian,
                    "tanggal" => $request->tanggal_transaksi,
                ]);

                $newItemIds = collect($request->items)
                    ->pluck("id")
                    ->filter()
                    ->toArray();

                // 2. HAPUS ITEM YANG TIDAK ADA DI REQUEST (Tracking Item Hilang)
                foreach ($pembelian->items as $oldItem) {
                    if (!in_array($oldItem->id, $newItemIds)) {
                        $this->rollbackStokItem($oldItem); // Fungsi bantu untuk kurangi stok
                        $oldItem->details()->delete();
                        $oldItem->delete();
                    }
                }

                // 3. ITERASI ITEMS DARI REQUEST
                foreach ($request->items as $itemData) {
                    $pembelianItem = $pembelian->items()->updateOrCreate(
                        ["id" => $itemData["id"] ?? null],
                        [
                            "varian_id" => $itemData["varian_id"],
                            "total_jumlah" => count($itemData["details"]),
                            "total_qty" => collect($itemData["details"])->sum(
                                "panjang_isi",
                            ),
                        ],
                    );

                    $varian = ProdukVarian::with("stok")->findOrFail(
                        $itemData["varian_id"],
                    );

                    // Sinkronisasi Detail (Rolls)
                    $newDetailIds = collect($itemData["details"])
                        ->pluck("id")
                        ->filter()
                        ->toArray();

                    // A. Hapus detail (roll) yang dibuang oleh user di UI
                    foreach ($pembelianItem->details as $oldDetail) {
                        if (!in_array($oldDetail->id, $newDetailIds)) {
                            // Rollback stok fisik di gudang
                            $varian
                                ->details()
                                ->where("id", $oldDetail->produk_detail_id) // Pakai relasi yang baru kamu buat
                                ->where("status", "tersedia")
                                ->delete();

                            $varian->stok()->decrement("total_unit", 1);
                            $varian
                                ->stok()
                                ->decrement("total_qty", $oldDetail->qty);

                            $oldDetail->delete();
                        }
                    }

                    // B. Update atau Create Detail Baru
                    foreach ($itemData["details"] as $detailData) {
                        if (isset($detailData["id"])) {
                            // Jika update panjang pada roll yang sudah ada
                            $detail = $pembelianItem
                                ->details()
                                ->find($detailData["id"]);
                            $selisihQty =
                                $detailData["panjang_isi"] - $detail->qty;

                            $detail->update([
                                "qty" => $detailData["panjang_isi"],
                            ]);

                            // Update juga di stok fisik (produk_details)
                            $varian
                                ->details()
                                ->where("id", $detail->produk_detail_id)
                                ->update([
                                    "qty" => $detailData["panjang_isi"],
                                ]);

                            // Update akumulasi master varian
                            $varian->increment("panjang", $selisihQty);
                        } else {
                            // Jika tambah roll baru di item yang sudah ada
                            $newStokDetail = $varian->details()->create([
                                "gudang_id" => 2,
                                "qty" => $detailData["panjang_isi"],
                                "status" => "tersedia",
                                "satuan_id" => $itemData["satuan_id"] ?? 1,
                            ]);

                            $pembelianItem->details()->create([
                                "produk_detail_id" => $newStokDetail->id,
                                "qty" => $detailData["panjang_isi"],
                                "satuan_id" => $itemData["satuan_id"] ?? 1,
                            ]);

                            $varian->stok()->increment("total_unit", 1);
                            $varian
                                ->stok()
                                ->increment(
                                    "total_qty",
                                    $detailData["panjang_isi"],
                                );
                        }
                    }
                }
            });

            return redirect()
                ->route("grosir.penerimaan.index")
                ->with(
                    "message",
                    "Nota berhasil diperbarui dengan sinkronisasi",
                );
        } catch (\Exception $e) {
            return back()->with("message", "Gagal: " . $e->getMessage());
        }
    }

    /**
     * Fungsi pembantu untuk rollback stok total jika satu item pembelian dihapus
     */
    private function rollbackStokItem($item)
    {
        $varian = $item->varian;
        foreach ($item->details as $detail) {
            $varian
                ->details()
                ->where("id", $detail->produk_detail_id)
                ->delete();
        }
        $varian->stok()->decrement("total_unit", $item->total_jumlah);
        $varian->stok()->decrement("total_qty", $item->total_qty);
    }
    public function destroy($id)
    {
        $pembelian = Pembelian::with("items.details")->findOrFail($id);

        Log::info("=== MEMULAI PROSES HAPUS NOTA: {$pembelian->nomer} ===");

        try {
            DB::transaction(function () use ($pembelian) {
                foreach ($pembelian->items as $item) {
                    $varian = $item->varian;

                    if (!$varian) {
                        Log::warning(
                            "Varian tidak ditemukan untuk Item ID: {$item->id}, melewati rollback stok.",
                        );
                        continue;
                    }

                    Log::info("Proses Rollback Varian: {$varian->nama}");

                    // 1. Hapus Detail Stok Fisik (ProdukDetail)
                    foreach ($item->details as $detail) {
                        // Pastikan query delete ini spesifik
                        $deletedDetails = $varian
                            ->details()
                            ->where("qty", $detail->qty)
                            ->where("status", "tersedia")
                            ->limit(1)
                            ->delete();

                        Log::info(
                            "  - Hapus Detail Stok (qty: {$detail->qty}): " .
                                ($deletedDetails
                                    ? "Sukses"
                                    : "Gagal/Tidak Ditemukan"),
                        );
                    }

                    // 2. Kurangi akumulasi stok di Master Varian
                    $varian
                        ->stok()
                        ->decrement("total_unit", $item->total_jumlah);
                    $varian->stok()->decrement("total_qty", $item->total_qty);
                    Log::info(
                        "  - Update Stok Varian: Kurangi {$item->total_jumlah} Roll & {$item->total_qty} Qty",
                    );

                    // 3. Hapus Batch/Stok Header (Jika ada relasi)
                    // Hati-hati: Pastikan ini hanya menghapus stok yang terkait dengan nota ini
                    $varian
                        ->stok()
                        ->where("nomer", "like", $pembelian->nomer . "%")
                        ->delete();

                    // 4. Hapus detail pembelian (Rolls)
                    $item->details()->delete();
                    Log::info("  - Detail Pembelian Item berhasil dihapus.");

                    // 5. Hapus Item Pembelian
                    $item->delete();
                }

                // 6. Hapus Header Pembelian
                $pembelian->delete();
                Log::info(
                    "=== NOTA {$pembelian->nomer} BERHASIL DIHAPUS TOTAL ===",
                );
            });

            return redirect()
                ->route("grosir.penerimaan.index")
                ->with("message", "Nota berhasil dihapus");
        } catch (\Exception $e) {
            // Log error secara detail ke file storage/logs/laravel.log
            Log::error("GAGAL HAPUS NOTA: " . $e->getMessage(), [
                "nomer_nota" => $pembelian->nomer,
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString(),
            ]);

            return back()->with("message", "Gagal: " . $e->getMessage());
        }
    }

    public function updateData()
    {
        DB::transaction(function () {
            $oldTerimaBarang = DB::connection("sqlite_lama")
                ->table("purchase_invoices")
                ->get();

            foreach ($oldTerimaBarang as $oldter) {
                $penerimaan = Pembelian::create([
                    "supplier_id" => $oldter->supplier_id,
                    "nomer" => (string) Str::ulid(),
                    "tanggal" => $oldter->transaction_date,
                    "total_pembelian" => $oldter->total_purchase_roll,
                    "beban_ongkir" => $oldter->shipping_cost ?? 0,
                    "beban_biaya" => $oldter->other_cost ?? 0,
                    "status" => "DITERIMA",
                    "catatan" =>
                        "Migrasi: Total Old Length " .
                        $oldter->total_purchase_length,
                    "created_at" => $oldter->created_at,
                    "updated_at" => $oldter->updated_at,
                ]);

                $oldTerimaBarangItems = DB::connection("sqlite_lama")
                    ->table("purchase_invoice_items")
                    ->where("purchase_invoice_id", $oldter->id)
                    ->get();

                foreach ($oldTerimaBarangItems as $item) {
                    $oldProduct = DB::connection("sqlite_lama")
                        ->table("products")
                        ->find($item->product_id);
                    if (!$oldProduct) {
                        continue;
                    }

                    // 1-3. Sinkronisasi Kategori, Satuan, Produk (Tetap seperti kode Anda)

                    $kategori = Kategori::firstOrCreate(
                        ["slug" => Str::slug($oldProduct->name)],
                        [
                            "nama_kategori" => $oldProduct->name,
                            "status" => "aktif",
                        ],
                    );
                    $satuan = Satuan::firstOrCreate(
                        ["nama_satuan" => $oldProduct->unit_satuan ?? "Yard"],
                        ["inisial" => "Yd"],
                    );
                    $newProduk = Produk::firstOrCreate(
                        ["nama" => $oldProduct->name],
                        [
                            "kategori_id" => $kategori->id,
                            "satuan_id" => $satuan->id,
                            "status" => "aktif",
                        ],
                    );

                    // 4. Sinkronisasi Varian

                    $varian = ProdukVarian::find($oldProduct->id);

                    if (!$varian) {
                        $varian = ProdukVarian::create([
                            "id" => $oldProduct->id,
                            "sku" => $oldProduct->slug,
                            "produk_id" => $newProduk->id,
                            "gudang_id" => 2,
                            "nama" => $oldProduct->kode_warna ?? "Default",
                            "tipe" => $oldProduct->tipe,
                            "panjang" => 0,
                            "jumlah" => 0,
                            "is_paket" => false,
                            "harga_beli" => $oldProduct->cost_price ?? 0,
                            "harga_jual" => $oldProduct->selling_price ?? 0,
                        ]);
                    }
                    $stok = $varian->stok()->create([
                        "gudang_id" => 2,
                        "nomer" =>
                            $penerimaan->id .
                            "-" .
                            $varian->id .
                            "-" .
                            now()->format("YmdHis"),
                        "total_qty" => 0,
                        "total_unit" => 0,
                    ]);

                    $penerimaanItem = $penerimaan->items()->create([
                        "varian_id" => $varian->id,
                        "satuan_id" => $satuan->id,
                        "total_qty" => $item->total_lenght,
                        "total_jumlah" => $item->jumlah_roll,
                    ]);

                    // 7. Migrasi Detail Roll & HITUNG ULANG
                    $oldRolls = DB::connection("sqlite_lama")
                        ->table("jumlah_rolls")
                        ->where("purchase_invoice_item_id", $item->id)
                        ->get();

                    $panjangMasuk = 0;
                    $jumlahRollMasuk = 0;

                    foreach ($oldRolls as $roll) {
                        $detailFisik = $varian->details()->create([
                            "gudang_id" => 2,
                            "stok_id" => $stok->id,
                            "old_id" => $roll->sale_item_id,
                            "satuan_id" => $satuan->id,
                            "qty" => $roll->panjang ?? 0,
                            "status" => $roll->status,
                        ]);

                        $detailFisik->kartuStoks()->create([
                            "varian_id" => $detailFisik->varian_id,
                            "dari_gudang_id" => 0,
                            "ke_gudang_id" => 2,
                            "tipe" => "masuk",
                            "qty" => $roll->panjang,
                            "stok_awal" => $roll->panjang,
                            "stok_akhir" => $roll->panjang,
                            "keterangan" => "masuk dari penerimaan",
                            "user_id" => Auth::id(),
                        ]);

                        Log::info(
                            "masukin id saleke" .
                                $detailFisik->id .
                                "sale id" .
                                $roll->sale_item_id,
                        );

                        $penerimaanItem->details()->create([
                            "produk_detail_id" => $detailFisik->id,
                            "satuan_id" => $satuan->id,
                            "qty" => $roll->panjang ?? 0,
                            "lot_number" => $roll->kode_roll ?? "-",
                        ]);

                        $panjangMasuk += $roll->panjang ?? 0;
                        $jumlahRollMasuk += 1;
                    }

                    $stok->increment("total_qty", $panjangMasuk);
                    $stok->increment("total_unit", $jumlahRollMasuk);
                }
            }
        });

        return redirect()
            ->route("grosir.penerimaan.index")
            ->with("message", "Refaktoring Data Berhasil & Stok Sinkron");
    }

    public function comparing()
    {
        // 1. Ambil data baru (MySQL)
        $dataBaru = ProdukVarian::with("produk")->get();

        // 2. Ambil data lama (SQLite)
        $dataLama = DB::connection("sqlite_lama")
            ->table("products")
            ->select("id", "total_lenght", "name", "slug")
            ->get()
            ->keyBy("id");

        $results = $dataBaru->map(function ($new) use ($dataLama) {
            // Karena ID sama, get() akan langsung mengambil object yang tepat
            $old = $dataLama->get($new->id);

            $stokBaru = (float) ($new->panjang ?? 0);
            $stokLama = $old ? (float) ($old->total_lenght ?? 0) : 0;
            $selisih = $stokBaru - $stokLama;

            return [
                "id" => $new->id,
                "nama" => $new->produk->nama,
                "sku_baru" => $new->sku,
                "stok_baru" => $stokBaru,
                "stok_lama" => $stokLama,
                "selisih" => round($selisih, 2),
                "is_sync" => abs($selisih) < 0.1,
                "found" => !is_null($old),
            ];
        });

        // OPSIONAL: Cek apakah ada data di SQLite yang belum masuk ke MySQL
        // Ini berguna untuk memastikan migrasi sudah 100%
        $idBaru = $dataBaru->pluck("id")->toArray();
        $dataKetinggalan = $dataLama->forget($idBaru);

        return inertia("Grosir/Comparing", [
            "results" => $results,
            "missing_count" => $dataKetinggalan->count(),
        ]);
    }
}
