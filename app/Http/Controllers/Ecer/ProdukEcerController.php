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
        $gudangId = 1;
        // dd($request->kategori);
        $products = ProdukVarian::with([
            'produk.kategori',
            'produk.satuan',
            'details'
        ])->where('gudang_id', 1)
            ->where('panjang', '>', 0)
            ->when($request->search, function ($q) use ($request) {
                $q->where('nama_varian', 'like', "%{$request->search}%")
                    ->orWhereHas('produk', function ($pq) use ($request) {
                        $pq->where('nama_produk', 'like', "%{$request->search}%");
                    });
            })
            ->when($request->kategori, function ($q) use ($request) {
                $q->whereHas('produk', function ($pq) use ($request) {
                    $pq->where('kategori_id', $request->kategori);
                });
            })
            ->paginate($perPage)
            ->withQueryString();

        // dd($products->first()->stokFisiks);
        $allProduk = Produk::with([
            'kategori',
            'varian' => function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)
                    ->with('details');
            }
        ])->get();

        return Inertia::render('Ecer/Produk', [
            'varians' => $products,
            'categories' => Kategori::orderBy('id', 'asc')->get(),
            'nama_produks' => Kategori::where('status', 'nama')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'semua_produk' => $allProduk,
            'suppliers' => Supplier::orderBy('id')->get(),
            'pelanggans' => Pelanggan::orderBy('id')->get(),
            'satuan' => Satuan::orderBy('id')->get(),
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

        // dd($request) 

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

            // 3. Kelola Varian Produk
            $varian = ProdukVarian::firstOrCreate(
                [
                    'produk_id' => $produk->id,
                    'nama' => $request->kode_warna
                ],
                [
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
                ]
            );

            // 4. Kelola Stok (Hanya buat jika belum ada stok untuk varian ini di gudang tsb)
            $stok = $varian->stok()->firstOrCreate(
                ['gudang_id' => 1],
                [
                    'nomor' => 'STK-' . str_pad($varian->id, 10, '0', STR_PAD_LEFT),
                    'tanggal_kadaluarsa' => null,
                ]
            );

            // 5. Simpan Detail/History

            $varian->details()->create([
                'stok_id'   => $stok->id,
                'satuan_id' => $request->satuan_id,
                'qty'       => $request->panjang ?? 0,
                'isi_2'     => 0,
                'status'    => 'tersedia',
            ]);

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

        // 3. Logic Kategori
        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));
        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
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

        // 6. UPDATE Stok Fisik (Penting: Gunakan first() atau perulangan)
        // Asumsi: Satu varian hanya punya satu record stok fisik utama
        $stok = $varian->stok()->first();
        if ($stok) {
            $stok->update([
                'gudang_id'     => 1,
                'nomor_batch'   => '-',
                'total_panjang' => $request->panjang ?? 0,
                'total_qty'     => 1,
            ]);
        }

        // 7. UPDATE Detail Isi Paket
        $detail = $varian->details()->first();
        if ($detail) {
            $detail->update([
                'satuan_id' => $request->satuan_id,
                'qty'       => $request->panjang ?? 0,
                'status'    => 'tersedia',
            ]);
        }

        return back()->with('success', 'Produk dan Varian berhasil diperbarui');
    }
}
