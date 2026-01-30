<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\ProdukDetail;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StokEcerController extends Controller
{
    public function index(Request $request)
    {
        $query = Stok::with(['varian.produk', 'details.satuan'])->where('gudang_id', 1);

        // Logika Search agar tidak error saat filter digunakan
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nomor', 'like', "%{$request->search}%")
                    ->orWhereHas('varian.produk', function ($p) use ($request) {
                        $p->where('nama', 'like', "%{$request->search}%");
                    });
            });
        }

        // Gunakan paginate agar variabel stoks.data di Vue tidak undefined
        $stok = $query->latest()->paginate($request->perPage ?? 10)->withQueryString();

        return inertia('Ecer/Stok/StokList', [
            'stoks' => $stok,
        ]);
    }

    public function createTransfer()
    {
        // Ambil data produk & varian dari gudang utama (Grosir) untuk dipilih
        $produkGrosir = Stok::with([
            'varian.produk',
            'details' => function ($query) {
                $query->where('status', 'tersedia')->with('satuan');
            }
        ])
            ->get();

        return inertia('Ecer/Stok/TransferForm', [
            'produkGrosir' => $produkGrosir
        ]);
    }

    public function storeTransfer(Request $request)
    {
        // dd($request);
        $request->validate([
            'selected_rolls' => 'required|array|min:1',
            'selected_rolls.*.stok_id' => 'required|exists:stoks,id',
            'selected_rolls.*.details' => 'required|array|min:1',
            'selected_rolls.*.details.*.id' => 'required|exists:produk_details,id',
            'selected_rolls.*.details.*.qty' => 'required|numeric',
        ], [
            'selected_rolls.required' => 'Pilih minimal satu roll untuk dipindahkan.',
            'selected_rolls.*.id.exists' => 'Salah satu stok tidak ditemukan di database.',
            'selected_rolls.*.details.*.id.exists' => 'Salah satu roll tidak ditemukan di database.',
        ]);

        // dd($request);
        DB::transaction(function () use ($request) {

            foreach ($request->selected_rolls as $stok) {
                $stokGrosir = Stok::findOrFail($stok['stok_id']);

                $stokEcer = Stok::create([
                    'nomer'      => 'TRF-' . now()->format('YmdHisv'),
                    'gudang_id'  => 1,
                    'varian_id'  => $stokGrosir->varian_id,
                    'total_qty'  => 0,
                    'total_unit' => 1,
                ]);

                $totalQty = 0;
                $totalUnit = 0;

                $stokEcerId = $stokEcer->id;

                foreach ($stok['details'] as $rollData) {


                    // 1. Ambil data asli Roll & Stok Asal
                    $detail = ProdukDetail::findOrFail($rollData['id']);

                    $detail->update([
                        'stok_id'   => $stokEcerId,
                        'gudang_id' => 1
                    ]);

                    $detail->kartuStoks()->create([
                        'varian_id'     => $detail->varian_id,
                        'dari_gudang_id' => 2, // Grosir
                        'ke_gudang_id'   => 1, // Ecer
                        'tipe'           => 'transfer',
                        'qty'            => $detail->qty,
                        'stok_awal'      => $detail->qty,
                        'stok_akhir'     => $detail->qty,
                        'keterangan'     => 'Transfer dari Grosir ke Ecer',
                        'user_id'        => Auth::id(),
                    ]);
                    $totalQty += $detail->qty;
                    $totalUnit += 1;
                }

                $stokEcer->update([
                    'total_qty'  => $totalQty,
                    'total_unit' => $totalUnit,
                ]);


                $stokGrosir->decrement('total_unit', $totalUnit);
                $stokGrosir->decrement('total_qty', $totalQty);
            }
        });

        return redirect()->route('ecer.stok.index')->with('success', 'Produk berhasil ditransfer.');
    }
}
