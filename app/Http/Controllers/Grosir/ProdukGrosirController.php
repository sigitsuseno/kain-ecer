<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
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
        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => 'required|string|max:255',
            'satuan' => 'required|in:meter,yard,kg',
            'deskripsi' => 'nullable|string',
            'tipe' => 'nullable|string',
            'lebar_kain' => 'nullable|string',
        ]);

        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));
        $kode_warnaClean = trim(ucwords(strtolower($request->kode_warna)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        $warna = Kategori::firstOrCreate(
            ['slug' => Str::slug($kode_warnaClean)],
            ['nama_kategori' => $kode_warnaClean, 'status' => 'warna']
        );

        $data['kategori_id'] = $kategori->id;
        $data['warna_id'] = $warna->id;
        $data['nama_produk'] = $namaProdukClean;
        $data['kode_warna'] = $kode_warnaClean;
        $data['gudang_id'] = 1;

        $data['kode_produk'] = 'GR-' . date('ym') . '-' . strtoupper(Str::random(6));
        $data['slug'] = Str::slug($request->nama_produk) . '-' . Str::slug($request->kode_warna);

        Produk::create($data);

        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => 'required|string|max:255',
            'satuan' => 'required|in:meter,yard,kg',
            'deskripsi' => 'nullable|string',
            'tipe' => 'nullable|string',
            'lebar_kain' => 'nullable|string',
        ]);

        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));
        $kode_warnaClean = trim(ucwords(strtolower($request->kode_warna)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        $warna = Kategori::firstOrCreate(
            ['slug' => Str::slug($kode_warnaClean)],
            ['nama_kategori' => $kode_warnaClean, 'status' => 'warna']
        );

        $data['kategori_id'] = $kategori->id;
        $data['warna_id'] = $warna->id;
        $data['nama_produk'] = $namaProdukClean;
        $data['kode_warna'] = $kode_warnaClean;
        $data['gudang_id'] = 1;

        $data['kode_produk'] = 'GR-' . date('ym') . '-' . strtoupper(Str::random(6));
        $data['slug'] = Str::slug($request->nama_produk) . '-' . Str::slug($request->kode_warna);

        $produk->update($data);

        return back()->with('success', 'Produk berhasil diperbarui');
    }
}
