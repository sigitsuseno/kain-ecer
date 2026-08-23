<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\ProdukVarian;
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

        $nama = $request->input('nama_produk') ?? $request->input('nama');
        $produk = Produk::where('nama', $nama)->first();
        if (!$produk) return response()->json([]);
        return ProdukVarian::where('produk_id', $produk->id)->pluck('nama');
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => [
                'required',
                'string',
                'max:100',
                Rule::unique('produk_varians', 'nama')->where(
                    fn($q) =>
                    $q->whereHas('produk', fn($qq) => $qq->where('nama', $request->nama_produk))
                ),
            ],
            'satuan_id' => 'nullable|exists:satuans,id',
            'satuan' => 'nullable|in:meter,yard,kg',
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
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        $produk = Produk::firstOrCreate(
            ['nama' => $namaProdukClean, 'kategori_id' => $kategori->id],
            [
                'satuan_id' => $request->satuan_id ?? 1,
                'deskripsi' => $request->deskripsi,
            ]
        );

        ProdukVarian::firstOrCreate(
            ['produk_id' => $produk->id, 'nama' => trim($request->kode_warna)],
            [
                'gudang_id' => $request->gudang_id ?? 2,
                'tipe' => $request->tipe ?? 'polos',
                'lebar' => is_numeric($request->lebar_kain) ? $request->lebar_kain : 0,
                'sku' => 'SKU-' . strtoupper(Str::random(6)),
                'is_paket' => false,
            ]
        );

        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, Produk $produk)
    {
        $data = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kode_warna' => [
                'required',
                Rule::unique('produk_varians', 'nama')
                    ->where(fn($q) => $q->where('produk_id', $produk->id))
                    ->ignore($produk->id),
            ],
            'satuan_id' => 'nullable|exists:satuans,id',
            'satuan' => 'nullable|in:meter,yard,kg',
            'gudang_id' => 'nullable|exists:gudangs,id',
            'deskripsi' => 'nullable|string',
            'kode_seri' => 'nullable|string',
            'tipe' => 'nullable|string',
            'lebar_kain' => 'nullable|string',
        ]);

        $namaProdukClean = trim(ucwords(strtolower($request->nama_produk)));

        $kategori = Kategori::firstOrCreate(
            ['slug' => Str::slug($namaProdukClean)],
            ['nama_kategori' => $namaProdukClean, 'status' => 'nama']
        );

        $produk->update([
            'nama' => $namaProdukClean,
            'kategori_id' => $kategori->id,
            'satuan_id' => $request->satuan_id ?? $produk->satuan_id,
            'deskripsi' => $request->deskripsi ?? $produk->deskripsi,
        ]);

        return back()->with('success', 'Produk berhasil diperbarui');
    }
}
