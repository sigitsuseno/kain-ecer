<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\DetailPaket;
use App\Models\DetailPenjualan;
use App\Models\ItemPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\PenjualanItem;
use App\Models\Perusahaan;
use App\Models\ProdukDetail;
use App\Models\ProdukVarian;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiPenjualanEceranController extends Controller
{
    public function list(Request $request)
    {
        $query = Penjualan::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details'
        ])->where('status', 'UNPAID');

        // Tambahkan filter search sederhana jika dibutuhkan
        if ($request->search) {
            $query->where('nomer_nota', 'like', "%{$request->search}%")
                ->orWhereHas('pelanggan', function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->search}%");
                });
        }
        return inertia('Ecer/Penjualan/List', [
            'penjualan' => $query->latest()->get(),
        ]);
    }

    public function index(Request $request)
    {

        $penjualan = Penjualan::with([
            'items.varian.produk',
            'items.varian.details',
            'pelanggan',
            'marketing'
        ])
            ->where('status', 'DRAFT')
            ->first();

        return inertia('Ecer/Penjualan/Kasir', [
            'draft' => $penjualan,
            'pelanggan' => Pelanggan::orderBy('nama')->get(),
            'marketing' => Supplier::where('status', 'marketing')->get(),
            'produk' => ProdukVarian::with([
                'produk',
                'stok',
                'details' => fn($q) => $q->where('qty', '>', 0)->with('satuan'),
            ])->get(),
            'company' => Perusahaan::find(1) ?? null,
        ]);
    }

    public function storeDraft(Request $request)
    {
        // dd($request);
        $data = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'pelanggan' => 'required|string',
            'marketing' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {

            $slugPelanggan = Str::slug($data['pelanggan']);

            $pelanggan = Pelanggan::where('slug', $slugPelanggan)->first();

            if (!$pelanggan) {
                $pelanggan = Pelanggan::firstOrCreate(
                    ['slug' => $slugPelanggan],
                    ['nama' => $data['pelanggan']]
                );
            }

            $marketing = null;

            if (!empty($data['marketing'])) {
                $slugMarketing = Str::slug($data['marketing']);

                $marketing = Supplier::firstOrCreate(
                    ['slug' => $slugMarketing],
                    [
                        'nama' => $data['marketing'],
                        'status' => 'marketing'
                    ]
                );
            }

            $penjualan = Penjualan::updateOrCreate(
                [
                    'pelanggan_id' => $pelanggan->id,
                    'status' => 'DRAFT',
                ],
                [
                    'nomer' => $this->generateNota(),
                    'tanggal' => $data['tanggal_transaksi'],
                    'marketing_id' => $marketing?->id,
                    'total_penjualan' => 0,
                    'diskon' => 0,
                    'pajak' => 0,
                    'ongkir' => 0,
                    'kembalian' => 0,
                ]
            );

            return redirect()->back()->with('draft_id', $penjualan->id);
        });
    }

    protected function generateNota(): string
    {
        $prefix = 'INV-' . now()->format('Ym');

        $last = Penjualan::where('nomer', 'like', "$prefix%")
            ->orderByDesc('id')
            ->value('nomer');

        $number = $last
            ? (int) substr($last, -4) + 1
            : 1;

        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function simpanItems(Request $request)
    {

        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:produk_varians,id',
            'items.*.satuan_id' => 'required',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.details' => 'required|array|min:1',
            'items.*.details.*.roll_id' => 'required',
            'items.*.details.*.qty' => 'required|numeric|min:0.01',
        ]);

        // dd($request);

        return DB::transaction(function () use ($request) {

            $penjualan = Penjualan::with(['items.details', 'items.varian.details'])->findOrFail($request->penjualan_id);

            $grandTotal = 0;

            foreach ($request->items as $item) {

                $totalQtyVarian = 0;

                $item_penjualan = $penjualan->items()->create([
                    'varian_id' => $item['variant_id'],
                    'satuan_id' => $item['satuan_id'],
                    'harga_satuan' => $item['harga'],
                ]);


                $itemPid = $item_penjualan->id;

                foreach ($item['details'] as $detail) {

                    $p_detail = ProdukDetail::findOrFail($detail['roll_id']);

                    // VALIDASI STOK: Cek apakah qty yang diinput melebihi stok fisik roll
                    if ($detail['qty'] > $p_detail->qty) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' => "Stok Roll ID {$detail['roll_id']} tidak mencukupi. Sisa: {$p_detail->qty}, Input: {$detail['qty']}"
                        ]);
                    }

                    $detail = PenjualanDetail::create([
                        'penjualan_item_id' => $itemPid,
                        'satuan_id' => $p_detail->satuan_id,
                        'qty' => $detail['qty'],
                        'status' => 'transisi',
                    ]);

                    $samaBanyak = $p_detail->qty - $detail['qty'] === 0;
                    if ($samaBanyak) {
                        $p_detail->delete();
                    } else {
                        $p_detail->decrement('qty', $detail['qty']);
                    }

                    $totalQtyVarian += $detail['qty'];
                }


                $subtotal = $totalQtyVarian * $item['harga'];

                $item_penjualan->update([
                    'qty' => $totalQtyVarian,
                    'subtotal' => $subtotal,
                ]);

                $grandTotal += $subtotal;
            }

            Penjualan::where('id', $request->penjualan_id)->update([
                'total_penjualan' => $grandTotal,
                'status' => 'UNPAID',
            ]);

            return redirect()->route('ecer.penjualan.pembayaran.prepare', $request->penjualan_id);
        });
    }

    public function hapusPenjualan(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $penjualan = Penjualan::findOrFail($id);

            $penjualan->items()->delete();

            $penjualan->delete();

            return redirect()->back();
        });
    }
}
