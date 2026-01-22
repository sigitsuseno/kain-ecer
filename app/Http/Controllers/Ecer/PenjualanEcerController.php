<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;

class PenjualanEcerController extends Controller
{
    public function index(Request $request)
    {
        $query = Penjualan::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details'
        ]);

        // Tambahkan filter search sederhana jika dibutuhkan
        if ($request->search) {
            $query->where('nomer', 'like', "%{$request->search}%")
                ->orWhereHas('pelanggan', function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->search}%");
                });
        }
        return inertia('Ecer/Penjualan', [
            'penjualan' => $query->latest()->get(),
        ]);
    }
}
