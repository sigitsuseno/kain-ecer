<?php

namespace App\Http\Controllers\Grosir;

use App\Http\Controllers\Controller;
use App\Models\Stok;
use Illuminate\Http\Request;

class StokGrosirController extends Controller
{
    public function index(Request $request)
    {
        $query = Stok::with(['varian.produk', 'details.satuan'])->where('gudang_id', 2);

        // Logika Search agar tidak error saat filter digunakan
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nomer', 'like', "%{$request->search}%")
                    ->orWhereHas('varian.produk', function ($p) use ($request) {
                        $p->where('nama', 'like', "%{$request->search}%");
                    });
            });
        }

        // Gunakan paginate agar variabel stoks.data di Vue tidak undefined
        $stok = $query->latest()->paginate($request->perPage ?? 10)->withQueryString();

        return inertia('Grosir/Stok/StokList', [
            'stoks' => $stok,
        ]);
    }
}
