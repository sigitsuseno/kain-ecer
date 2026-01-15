<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrosirController extends Controller
{
    public function index()
    {
        return inertia('Grosir');
    }

    public function master(Request $request)
    {
        $perPage = 10;
        $gudangId = 1;

        $products = Produk::with(['kategori', 'warna'])
            ->where('gudang_id', $gudangId)
            ->when(
                $request->search,
                fn($q) =>
                $q->where('kode_warna', 'like', "%{$request->search}%")
                    ->orWhere('nama_produk', 'like', "%{$request->search}%")
            )
            ->when(
                $request->kategori,
                fn($q) =>
                $q->where('kategori_id', $request->kategori)
            )
            ->orderBy('kode_warna', 'asc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                $rolls = Produk::where('nama_produk', $item->nama_produk)
                    ->where('kode_warna', $item->kode_warna)
                    ->where('gudang_id', $item->gudang_id)
                    ->get(['id', 'panjang_perroll', 'kode_produk']);

                return [
                    'id' => $item->id,
                    'nama_produk' => $item->nama_produk,
                    'kode_warna' => $item->kode_warna,
                    'kategori' => $item->kategori->nama_kategori ?? '-',
                    'warna_kategori' => $item->warna->nama_kategori ?? '-',
                    'total_roll' => $rolls->count(),
                    'total_yard' => $rolls->sum('panjang_perroll'),
                    'rincian_rolls' => $rolls
                ];
            });

        $semuaProduk = Produk::with(['kategori', 'warna'])
            ->where('gudang_id', $gudangId)
            ->where('status', 'tersedia')
            ->get()
            ->groupBy('kode_warna')
            ->map(function ($items, $warna) {
                return [
                    'kode_warna' => $warna,
                    'nama_produk' => $items->first()->nama_produk,
                    'total_roll' => $items->count(),
                    // Simpan detail roll dalam bentuk array objek
                    'rolls' => $items->map(function ($roll) {
                        return [
                            'id' => $roll->id,
                            'kode_produk' => $roll->kode_produk,
                            'panjang' => $roll->panjang_perroll,
                        ];
                    })
                ];
            })->values();

        return Inertia::render('Grosir/Master', [
            'products' => $products,
            'categories' => Kategori::orderBy('id', 'asc')->get(),
            'nama_produks' => Kategori::where('status', 'nama')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'semua_produk' => $semuaProduk,
            'suppliers' => Supplier::orderBy('id')->get(),
            'pelanggans' => Pelanggan::orderBy('id')->get(),
        ]);
    }
}
