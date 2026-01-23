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
use App\Models\Satuan;
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
            ])
                ->whereHas('details', fn($q) => $q->where('qty', '>', 0))
                ->get(),
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

    // public function simpanItems(Request $request)
    // {

    //     $request->validate([
    //         'penjualan_id' => 'required|exists:penjualans,id',
    //         'items' => 'required|array|min:1',
    //         'items.*.variant_id' => 'required|exists:produk_varians,id',
    //         'items.*.harga' => 'required|numeric|min:0',
    //         'items.*.details' => 'required|array|min:1',
    //         'items.*.details.*.roll_id' => 'required',
    //         'items.*.details.*.qty' => 'required|numeric|min:0.01',
    //     ]);

    //     // dd($request);

    //     return DB::transaction(function () use ($request) {

    //         $penjualan = Penjualan::with(['items.details', 'items.varian.details'])->findOrFail($request->penjualan_id);

    //         $grandTotal = 0;

    //         foreach ($request->items as $item) {

    //             $totalQtyVarian = 0;
    //             $subtotaljmlhHarga = 0;

    //             $item_penjualan = $penjualan->items()->create([
    //                 'varian_id' => $item['variant_id'],
    //                 'harga_satuan' => $item['harga'],
    //             ]);

    //             $itemPid = $item_penjualan->id;

    //             foreach ($item['details'] as $detail) {

    //                 $p_detail = ProdukDetail::findOrFail($detail['roll_id']);

    //                 // VALIDASI STOK: Cek apakah qty yang diinput melebihi stok fisik roll

    //                 $satuanBeli = Satuan::where('nama_satuan', $detail['satuan_beli'])->first()->id;
    //                 $realQtyOut = round($detail['qty'] / $detail['koefisien'], 2);

    //                 if ($detail['qty'] > $p_detail->qty) {
    //                     throw \Illuminate\Validation\ValidationException::withMessages([
    //                         'items' => "Stok Roll ID {$detail['roll_id']} tidak mencukupi. Sisa: {$p_detail->qty}, Input: {$detail['qty']}"
    //                     ]);
    //                 }
    //                 // 1. Hitung harga konversi asli (jangan dibulatkan dulu)
    //                 $hargaKonversiAsli = $item['harga'] / $detail['koefisien'];

    //                 // 2. Hitung jumlah harga asli (Qty x Harga Asli)
    //                 $jumlahHargaAsli = $detail['qty'] * $hargaKonversiAsli;

    //                 // 3. BARU BULATKAN TOTALNYA KE ATAS 500
    //                 $jumlahHargaDibulatkan = ceil($jumlahHargaAsli / 500) * 500;

    //                 $penjualanDetail = PenjualanDetail::create([
    //                     'penjualan_item_id' => $itemPid,
    //                     'satuan_id' => $satuanBeli ?? null,
    //                     'qty' => $detail['qty'],
    //                     'harga' => $hargaKonversiAsli, // Simpan harga asli agar nota akurat
    //                     'jumlah_harga' => $jumlahHargaDibulatkan, // Simpan total yang sudah bulat
    //                     'status' => 'transisi',
    //                 ]);

    //                 $subtotaljmlhHarga += $jumlahHargaDibulatkan;

    //                 $samaBanyak = $p_detail->qty - $realQtyOut < 0.1;
    //                 if ($samaBanyak) {
    //                     $p_detail->delete();
    //                 } else {
    //                     $p_detail->decrement('qty', $realQtyOut);
    //                 }

    //                 $totalQtyVarian += $realQtyOut;
    //             }


    //             $subtotal = $subtotaljmlhHarga;

    //             $item_penjualan->update([
    //                 'qty' => $totalQtyVarian,
    //                 'subtotal' => $subtotal,
    //             ]);

    //             $grandTotal += $subtotal;
    //         }

    //         Penjualan::where('id', $request->penjualan_id)->update([
    //             'total_penjualan' => $grandTotal,
    //             'status' => 'UNPAID',
    //         ]);

    //         return redirect()->route('ecer.penjualan.pembayaran.prepare', $request->penjualan_id);
    //     });
    // }

    public function simpanItems(Request $request)
    {
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:produk_varians,id',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.details' => 'required|array|min:1',
            'items.*.details.*.roll_id' => 'required',
            'items.*.details.*.qty' => 'required|numeric|min:0.01',
        ]);

        // dd($request);

        return DB::transaction(function () use ($request) {
            $penjualan = Penjualan::findOrFail($request->penjualan_id);
            $grandTotal = 0;

            foreach ($request->items as $item) {
                $totalQtyJual = 0;
                $subtotalUangItem = 0;

                $item_penjualan = $penjualan->items()->create([
                    'varian_id' => $item['variant_id'],
                    'harga_satuan' => $item['harga'], // Harga dasar per Yard
                ]);

                foreach ($item['details'] as $detail) {
                    // LOCK FOR UPDATE: Mencegah data diubah transaksi lain saat proses ini berjalan
                    $p_detail = ProdukDetail::where('id', $detail['roll_id'])->lockForUpdate()->firstOrFail();

                    // 1. Hitung kebutuhan stok asli (Yard)
                    $realQtyOut = round($detail['qty'] / $detail['koefisien'], 2);

                    // 2. VALIDASI GUDANG: Mencegah stok minus
                    if ($realQtyOut > $p_detail->qty) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' => "Stok tidak cukup untuk {$item['nama']}. Tersedia: {$p_detail->qty}, Diminta: {$realQtyOut}"
                        ]);
                    }

                    // 3. LOGIKA PEMBULATAN (Total Baris ke atas 500)
                    $hargaKonversi = $item['harga'] / $detail['koefisien'];
                    $jumlahHargaAsli = $detail['qty'] * $hargaKonversi;
                    $jumlahHargaDibulatkan = ceil($jumlahHargaAsli / 500) * 500;

                    // 4. SIMPAN DETAIL
                    $satuanBeli = Satuan::where('nama_satuan', $detail['satuan_beli'])->first();

                    PenjualanDetail::create([
                        'penjualan_item_id' => $item_penjualan->id,
                        'satuan_id' => $satuanBeli->id ?? null,
                        'qty' => $detail['qty'], // Qty Meter
                        'harga' => $hargaKonversi,
                        'jumlah_harga' => $jumlahHargaDibulatkan,
                        'status' => 'transisi',
                    ]);

                    // 5. UPDATE STOK: Hapus jika sisa sangat sedikit, atau decrement
                    $sisaStok = $p_detail->qty - $realQtyOut;
                    if ($sisaStok <= 0.01) {
                        $p_detail->delete();
                    } else {
                        $p_detail->update(['qty' => $sisaStok]);
                    }

                    $subtotalUangItem += $jumlahHargaDibulatkan;
                    $totalQtyJual += $detail['qty']; // Akumulasi Qty Meter
                }

                // Update Header Item per Varian
                $item_penjualan->update([
                    'qty' => $totalQtyJual,
                    'subtotal' => $subtotalUangItem,
                ]);

                $grandTotal += $subtotalUangItem;
            }

            // Update Grand Total Penjualan
            $penjualan->update([
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
