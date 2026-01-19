<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\ProdukVarian;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProdukGrosirController extends Controller
{

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
            "kode_warna" => 'required|string|max:255',
            "satuan_id" => 'required',
            "tipe" => 'required|string|max:255',
            "lebar_kain" => 'nullable|string|max:255',
            "deskripsi" => 'nullable',
        ]);

        // dd($request);
        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        $kategoriId = $kategori->id;

        $produk = Produk::create([
            'nama_produk' => $request->nama_produk,
            'kategori_id' => $kategoriId,
            'satuan_id' => $request->satuan_id,
            'deskripsi' => $request->deskripsi,
        ]);

        ProdukVarian::create([
            'produk_id' => $produk->id,
            'gudang_id' => 1,
            'nama_varian' => $request->kode_warna,
            'tipe' => $request->tipe,
            'lebar' => $request->lebar_kain ?? 0,
        ]);


        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {

        $varian = ProdukVarian::with('produk')->findOrFail($id);
        $produkInduk = $varian->produk;

        // 2. Validasi
        $request->validate([
            "nama_produk" => 'required|string|max:255',
            "kode_warna"  => 'required|string|max:255',
            "satuan_id"   => 'required',
            "lebar_kain"  => 'nullable|numeric',
        ]);

        // 3. Logic Kategori (Tetap seperti milik Anda)
        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));
        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        // 4. UPDATE Produk Induk (Level 1)
        $produkInduk->update([
            'nama_produk' => $request->nama_produk,
            'kategori_id' => $kategori->id,
            'satuan_id'   => $request->satuan_id,
            'deskripsi'   => $request->deskripsi,
        ]);

        // 5. UPDATE Varian (Level 2)
        $varian->update([
            'nama_varian' => $request->kode_warna, // Pastikan nama kolom di DB 'nama_varian' atau 'kode_warna'
            'tipe'        => $request->tipe,
            'lebar'       => $request->lebar_kain ?: 0,
        ]);

        return back()->with('success', 'Produk dan Varian berhasil diperbarui');
    }
}
