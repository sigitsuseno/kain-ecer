<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;

class PenjualanEcerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 10);

        $query = Penjualan::query()
            ->with([
                'pelanggan:id,nama',
                'marketing:id,nama',
            ])->where('bagian', 'ecer');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nomer', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($qp) use ($search) {
                        $qp->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        return inertia('Ecer/Penjualan', [
            'penjualan' => $query
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }
}
