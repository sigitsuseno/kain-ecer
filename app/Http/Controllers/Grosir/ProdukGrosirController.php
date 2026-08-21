<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\ProdukVarian;
use App\Models\Satuan;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProdukGrosirController extends Controller
{
    public function index(Request $request)
    {
        // Ambil per_page dari request, default ke 10
        $perPage = $request->input('per_page', 10);

        $gudangId = 2;

        $products = ProdukVarian::with([
            'produk.satuan',
            'produk.kategori',
            'details' => function ($q) use ($gudangId) {
                $q->whereHas('stok', function ($query) use ($gudangId) {
                    $query->where('gudang_id', $gudangId);
                })->where('qty', '>', 0);
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

        return Inertia::render('Grosir/Master/Product', [
            'products' => $products,
            'filters' => $request->only(['search', 'per_page']), // Baris ini PENTING
            'categories' => Kategori::orderBy('id', 'asc')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'varian' => ProdukVarian::orderBy('nama')->get(),
            'satuan' => Satuan::all(),
        ]);
    }

    public function colorsByName(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string'
        ]);

        return Produk::where('nama_produk', $request->nama_produk)
            ->pluck('nama');
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

            // 3. Kelola Varian Produk
            $varian = ProdukVarian::firstOrCreate(
                [
                    'produk_id' => $produk->id,
                    'nama' => $request->kode_warna
                ],
                [
                    'gudang_id' => 2,
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



        return back()->with('success', 'Produk dan Varian berhasil diperbarui');
    }

    public function destroy($id)
    {
        $varian = ProdukVarian::findOrFail($id);

        // Opsional: Cek apakah masih ada stok atau detail produk
        if ($varian->details()->count() > 0) {
            return back()->with('error', 'Produk tidak bisa dihapus karena sudah memiliki data stok.');
        }

        $varian->delete();

        return back()->with('message', 'Produk berhasil dihapus');
    }
}
