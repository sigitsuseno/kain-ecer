<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return inertia('Dashboard');
    }

    public function master(Request $request)
    {
        $perPage = 10;

        $products = Produk::with(['kategori', 'gudang'])
            ->when(
                $request->search,
                fn($q) =>
                $q->where('kode_warna', 'like', "%{$request->search}%")
            )
            ->when(
                $request->kategori,
                fn($q) =>
                $q->where('kategori_id', $request->kategori)
            )
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();


        return Inertia::render('Dashboard/Master', [
            'products' => $products,
            'categories' => Kategori::where('status', 'np')->get(),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(),
            'semua_produk' => Produk::all(),
            'suppliers' => Supplier::orderBy('id')->get(),
            'pelanggans' => Pelanggan::orderBy('id')->get(),
        ]);
    }
}
