<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\ProdukVarian;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranEcerController extends Controller
{
    public function index(Request $request)
    {
        $query = Pembayaran::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details'
        ])->where('status', 'UNPAID');

        // Tambahkan filter search sederhana jika dibutuhkan
        if ($request->search) {
            $query->where('nomer', 'like', "%{$request->search}%")
                ->orWhereHas('pelanggan', function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->search}%");
                });
        }

        return inertia('Ecer/Penjualan/ListPembayaran', [
            'penjualan' => $query->latest()->get(),
        ]);
    }

    public function preparePembayaran($id)
    {
        $penjualan = Penjualan::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details'
        ])->findOrFail($id);

        // Tambahkan baris ini untuk memformat tanggal
        $penjualan->tanggal_indo = \Carbon\Carbon::parse($penjualan->tanggal)->isoFormat('D MMMM YYYY');

        return inertia('Ecer/Penjualan/Pembayaran', [
            'penjualan' => $penjualan,
            'pelanggan' => Pelanggan::orderBy('nama')->get(),
            'marketing' => Supplier::where('status', 'marketing')->get(),
            'produk' => ProdukVarian::with([
                'produk',
                'stok',
                'details' => fn($q) => $q->where('qty', '>', 0),
            ])->get(),
        ]);
    }

    public function bayar(Request $request, Penjualan $penjualan)
    {
        $data = $request->validate([
            'tanggal_bayar' => 'required|date',
            'metode_bayar' => 'required|in:CASH,TRANSFER,EDC,SPLIT,DP',
            'total_bayar' => 'required|numeric|min:0',
            'ongkir' => 'nullable|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'details' => 'required|array|min:1',
            'details.*.metode_bayar' => 'required|string',
            'details.*.bank_asal' => 'nullable|string',
            'details.*.bank_tujuan' => 'nullable|string',
            'details.*.ref_pembayaran' => 'nullable|string',
            'details.*.nominal' => 'required|numeric|min:0',
        ]);


        return DB::transaction(function () use ($penjualan, $data) {
            $tglBayar = Carbon::parse($data['tanggal_bayar']);
            $totalTagihan = ($penjualan->total_harga + $data['ongkir']) - $data['diskon'];
            $isLunas = $data['total_bayar'] >= $totalTagihan;
            // 1. Update Ongkir & Diskon di Tabel Penjualan
            $penjualan->update([
                'ongkir' => $data['ongkir'] ?? 0,
                'diskon' => $data['diskon'] ?? 0,
                'status' => $isLunas ? 'LUNAS' : 'DP',
            ]);


            $kembalian = $data['total_bayar'] - $totalTagihan;

            $kodeBayar = 'INVPY-' . $tglBayar->format('dmY') . '-' . str_pad($penjualan->id, 4, '0', STR_PAD_LEFT);

            $pembayaran = $penjualan->pembayaran()->create([
                'tanggal'    => $tglBayar,
                'kode'       => $kodeBayar,
                'total'      => $data['total_bayar'],
                'metode'     => $data['metode_bayar'],
                'keterangan' => 'Pembayaran via ' . $data['metode_bayar'],
                'is_dp'      => $data['metode_bayar'] === 'DP',
            ]);

            foreach ($data['details'] as $detail) {
                $pembayaran->details()->create([
                    'metode_bayar'     => $detail['metode_bayar'],
                    'bank_asal'        => $detail['bank_asal'] ?? null,
                    'akun_asal'        => '',
                    'bank_tujuan'      => $detail['bank_tujuan'] ?? null,
                    'no_ref'           => $detail['ref_pembayaran'] ?? null,
                    'jumlah_bayar'     => $data['total_bayar'],
                    'nominal'           => $detail['nominal'],
                    'kembalian'        => $kembalian > 0 ? $kembalian : 0,
                    'status' => $isLunas ? 'LUNAS' : 'DP',
                ]);
            }

            return redirect()
                ->route('ecer.penjualan.transaksi.list')
                ->with('success', 'Pembayaran berhasil diproses');
        });
    }
}
