<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Stok;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 25);

        $query = Stok::query()
            ->with([
                // Stok -> Varian -> Produk
                'varian:id,produk_id,nama,sku',
                'varian.produk:id,nama',

                // Stok -> Details (qty)
                'details:id,stok_id,qty',
            ]);

        // Search: nomor stok / nama produk / nama varian
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where('nomor', 'like', "%{$search}%")
                ->orWhereHas('varian', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($qp) use ($search) {
                            $qp->where('nama', 'like', "%{$search}%");
                        });
                });
        }

        return inertia('Ecer/Stok', [
            'stoks' => $query
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }
}
