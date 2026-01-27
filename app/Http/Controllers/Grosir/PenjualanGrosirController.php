<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;

class PenjualanGrosirController extends Controller
{
    public function index(Request $request)
    {
        $penjualan = Penjualan::with(['items.details.satuan', 'items.varian.produk', 'pelanggan', 'pembayaran'])
            ->where('bagian', 'grosir')
            ->when($request->search, function ($q) use ($request) {
                $q->where('nama_penjualan', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return inertia('Grosir/Penjualan/Transaksi', [
            'penjualan' => $penjualan,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create()
    {
        return inertia('Grosir/Penjualan/Create');
    }
}
