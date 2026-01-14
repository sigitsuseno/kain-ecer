<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
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

        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => [
                'required',
                'string',
                'max:100',
                Rule::unique('produks')->where(
                    fn($q) =>
                    $q->where('nama_produk', $request->nama_produk)
                ),
            ],
            'satuan' => 'required|in:meter,yard,kg',
            'gudang_id' => 'nullable|exists:gudangs,id',
            'deskripsi' => 'nullable|string',
            'kode_seri' => 'nullable|string',
            'tipe' => 'nullable|string',
            'lebar_kain' => 'nullable|string',
        ]);
        // normalisasi warna
        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean]
        );

        $data['kategori_id'] = $kategori->id;
        $data['nama_produk'] = $namaProdukClean;

        $data['kode_produk'] = 'PRD-' . strtoupper(Str::random(6));
        $data['slug'] = Str::slug($request->nama_produk);

        Produk::create($data);

        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => [
                'required',
                Rule::unique('produks')
                    ->ignore($produk->id)
                    ->where(
                        fn($q) =>
                        $q->where('nama_produk', $request->nama_produk)
                    ),
            ],
            'satuan' => 'required|in:meter,yard,kg',
            'gudang_id' => 'required|exists:gudangs,id',
            'deskripsi' => 'nullable|string',
            'kode_seri' => 'nullable|string',
            'tipe' => 'nullable|string',
            'lebar_kain' => 'nullable|string',
        ]);

        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean]
        );

        $data['kategori_id'] = $kategori->id;
        $data['nama_produk'] = $namaProdukClean;

        $data['slug'] = Str::slug($request->nama_produk);

        $produk->update($data);

        return back()->with('success', 'Produk berhasil diperbarui');
    }
}
