<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\ProdukDetail;
use App\Models\ProdukVarian;
use App\Models\Satuan;
use App\Models\Stok;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProdukEcerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $gudangId = 1; // Sesuaikan dengan logika gudang kamu
        $search = $request->input('search');

        // Kita mulai dari ProdukVarian tapi dikunci dengan whereHas Stok
        // Ini lebih efisien untuk ditampilkan ke tabel Vue kamu
        $products = ProdukVarian::with([
            'produk.kategori',
            'produk.satuan',
            'details' => function ($q) use ($gudangId) {
                $q->whereHas('stok', function ($query) use ($gudangId) {
                    $query->where('gudang_id', $gudangId);
                })->where('qty', '>', 0);
            },
            'stok' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            }
        ])
            // KUNCI: Hanya munculkan yang punya baris stok di gudang ini
            ->whereHas('stok', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)
                    ->where('total_qty', '>', 0); // Hanya yang ada sisa stoknya
            })
            // Filter Pencarian (Nama Produk atau Nama Varian/Warna)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_varian', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($pq) use ($search) {
                            $pq->where('nama_produk', 'like', "%{$search}%");
                        });
                });
            })
            // Filter Kategori
            ->when($request->kategori, function ($q) use ($request) {
                $q->whereHas('produk', function ($pq) use ($request) {
                    $pq->where('kategori_id', $request->kategori);
                });
            })
            ->paginate($perPage)
            ->withQueryString();

        // Data pendukung lainnya
        $allProduk = Produk::with(['kategori', 'varian.details'])->get();

        return Inertia::render('Ecer/Produk', [
            'varians' => $products, // Ini akan berisi data paginasi untuk tabel
            'categories' => Kategori::orderBy('nama_kategori', 'asc')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'allProducts' => $allProduk, // Pastikan prop ini sama dengan di Vue (allProducts)
            'satuan' => Satuan::all(),
            'filters' => $request->only(['search', 'kategori', 'perPage']),
        ]);
    }
    public function colorsByName(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string'
        ]);

        return Produk::where('nama_produk', $request->nama_produk)
            ->pluck('kode_warna');
    }

    public function store(Request $request)
    {
        $request->validate([
            "nama_produk" => 'required|string|max:255',
            "kode_warna"  => 'required|string|max:255',
            "satuan_id"   => 'required',
            "tipe"        => 'required|string|max:255',
            "lebar_kain"  => 'nullable|string|max:255',
            "panjang"     => 'nullable|numeric',
            "deskripsi"   => 'nullable',
        ]);

        // dd($request);

        try {
            DB::beginTransaction();

            $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));


            $kategori = Kategori::firstOrCreate(
                ['slug' => Str::slug($namaProdukClean)],
                ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
            );

            // 2. Kelola Produk Header
            $produk = Produk::firstOrCreate(
                ['nama' => $namaProdukClean, 'kategori_id' => $kategori->id],
                [
                    'satuan_id' => $request->satuan_id,
                    'deskripsi' => $request->deskripsi,
                ]
            );

            $varian = ProdukVarian::where('nama', $request->kode_warna)
                ->where('produk_id', $produk->id)
                ->first();

            if (!$varian) {
                $varian = ProdukVarian::create([
                    'produk_id' => $produk->id,
                    'nama'      => $request->kode_warna,
                    'gudang_id' => 1,
                    'tipe'      => $request->tipe,
                    'lebar'     => $request->lebar_kain ?? 0,
                    'panjang'   => $request->panjang ?? 0,
                    'tinggi'    => 0,
                    'tebal'     => 0,
                    'sku'       => 'SKU-' . strtoupper(Str::random(6)),
                    'is_paket'  => false,
                    'harga_beli' => 0,
                    'harga_jual' => 0,
                ]);

                // Buat Stok Header Baru
                $stok = $varian->stok()->create([
                    'gudang_id' => 1,
                    'nomor'     => 'STK-' . str_pad($varian->id, 10, '0', STR_PAD_LEFT),
                    'total_unit' => 0,
                    'total_qty'  => 0,
                ]);
            } else {
                $stok = $varian->stok()->where('gudang_id', 1)->first();
            }

            $detail = $varian->details()->create([
                'gudang_id' => 1,
                'stok_id'   => $stok->id,
                'satuan_id' => $request->satuan_id,
                'qty'       => $request->panjang ?? 0,
                'isi_2'     => 0,
                'status'    => 'tersedia',
            ]);

            // 5. Simpan Detail/History
            if ($detail->stok_id === $stok->id) {
                $stok->increment('total_unit', 1);
                $stok->increment('total_qty', $request->panjang ?? 0);
            } else {
                $stok->update([
                    'total_unit' => 1,
                    'total_qty' => $request->panjang ?? 0
                ]);
            }

            DB::commit();
            return back()->with('success', 'Produk dan Varian berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal simpan produk: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        // 1. Ambil data dengan relasi lengkap agar efisien
        $varian = ProdukVarian::with(['produk', 'stok', 'details'])->findOrFail($id);

        $produkInduk = $varian->produk;

        // 2. Validasi
        $request->validate([
            "nama_produk" => 'required|string|max:255',
            "kode_warna"  => 'required|string|max:255',
            "satuan_id"   => 'required',
            "lebar_kain"  => 'nullable|numeric',
        ]);

        // dd($request);
        // 3. Logic Kategori
        $kategori = Kategori::find($request->kategori_id);

        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));
        $kategori->update(
            [
                'slug' => Str::slug($namaProdukClean),
                'nama_kategori' => $namaProdukClean,
                'status' => 'nama'
            ]
        );

        // 4. UPDATE Produk Induk
        $produkInduk->update([
            'nama' => $request->nama_produk,
            'kategori_id' => $kategori->id,
            'satuan_id'   => $request->satuan_id,
            'deskripsi'   => $request->deskripsi,
        ]);

        // 5. UPDATE Varian
        $varian->update([
            'nama' => $request->kode_warna,
            'tipe'        => $request->tipe,
            'lebar'       => $request->lebar_kain ?: 0,
            'panjang' => $request->panjang ?? 0,
        ]);

        // 7. UPDATE Detail Isi Paket
        $detail = $varian->details()->first();
        $cekPanjang = $request->panjang !== $detail->qty ?? false;
        $selisihPanjang = $detail->qty - $request->panjang;
        $stok = $varian->stok;

        if ($cekPanjang) {
            $stok->decrement('total_qty', $selisihPanjang);
            $detail->update([
                'satuan_id' => $request->satuan_id,
                'qty'       => $request->panjang ?? 0,
                'status'    => 'tersedia',
            ]);
        }


        // 6. UPDATE Stok Fisik (Penting: Gunakan first() atau perulangan)
        // Asumsi: Satu varian hanya punya satu record stok fisik utama




        return back()->with('success', 'Produk dan Varian berhasil diperbarui');
    }
    // app/Http/Controllers/ProdukController.php (atau sesuai nama controller Anda)

    public function destroyDetail($id)
    {
        try {
            DB::beginTransaction();

            $detail = ProdukDetail::findOrFail($id);

            // Ambil stok terkait untuk dikurangi nilainya
            $stok = Stok::find($detail->stok_id);

            if ($stok) {
                $stok->decrement('total_unit', 1);
                $stok->decrement('total_qty', $detail->qty);
            }

            // Hapus detail produk
            $detail->delete();

            DB::commit();
            return back()->with('success', 'Roll produk berhasil dihapus dan stok diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus detail: ' . $e->getMessage());
        }
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt']);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header
        fgetcsv($handle, 1000, ",");

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // data[1] = Nama Produk, data[2] = Warna, data[3] = Qty/Panjang
                $namaProduk = trim($data[1]);
                $warna = trim($data[2]);
                $panjang = (float) $data[3];

                if (empty($namaProduk)) continue;

                // 1. Kategori & Produk (Logika Grouping)
                $kategori = Kategori::firstOrCreate(
                    ['slug' => Str::slug($namaProduk)],
                    ['nama_kategori' => $namaProduk, 'status' => 'nama']
                );

                $produk = Produk::firstOrCreate(
                    ['nama' => $namaProduk, 'kategori_id' => $kategori->id],
                    ['satuan_id' => 1] // Sesuaikan ID satuan default (misal: Meter)
                );

                // 2. Varian (Warna)
                $varian = ProdukVarian::firstOrCreate(
                    ['produk_id' => $produk->id, 'nama' => $warna],
                    [
                        'gudang_id' => 1,
                        'sku' => 'SKU-' . strtoupper(Str::random(6)),
                        'tipe' => 'polos'
                    ]
                );

                // 3. Stok Header
                $stok = Stok::firstOrCreate(
                    ['varian_id' => $varian->id, 'gudang_id' => 1],
                    ['nomor' => 'STK-' . str_pad($varian->id, 8, '0', STR_PAD_LEFT)]
                );

                // 4. Buat Detail (Roll) Baru
                ProdukDetail::create([
                    'varian_id' => $varian->id,
                    'stok_id'   => $stok->id,
                    'gudang_id' => 1,
                    'qty'       => $panjang,
                    'satuan_id' => 1,
                    'status'    => 'tersedia'
                ]);

                // 5. Update Total Stok
                $stok->increment('total_unit', 1);
                $stok->increment('total_qty', $panjang);
            }

            DB::commit();
            fclose($handle);
            return back()->with('success', 'Data berhasil diimport');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }
}
