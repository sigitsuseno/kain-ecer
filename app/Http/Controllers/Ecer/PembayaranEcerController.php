<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\BankPembayaran;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\ProdukVarian;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PembayaranEcerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 10);

        $query = Penjualan::query()
            ->with([
                'pelanggan:id,nama',
                'marketing:id,nama',
            ])->where('bagian', 'ecer')->whereIn('status', ['LUNAS', 'DP']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nomer', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($qp) use ($search) {
                        $qp->where('nama', 'like', "%{$search}%");
                    });
            });
        }
        return inertia('Ecer/Penjualan/ListPembayaran', [
            'penjualan' => $query
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    public function preparePembayaran($id)
    {
        $penjualan = Penjualan::with([
            'pelanggan',
            'marketing',
            'items.varian.produk',
            'items.details.satuan',
            'pembayaran',
        ])->findOrFail($id);

        // Tambahkan baris ini untuk memformat tanggal
        $penjualan->tanggal_indo = \Carbon\Carbon::parse($penjualan->tanggal)->isoFormat('D MMMM YYYY');

        $historyPembayaran = Pembayaran::where('penjualan_id', $id)
            ->with('details')
            ->get()
            ->flatMap(function ($pembayaran) {
                return $pembayaran->details;
            });

        return inertia('Ecer/Penjualan/Pembayaran', [
            'historyPembayaran' => $historyPembayaran,
            'penjualan' => $penjualan,
            'banks' => BankPembayaran::all(),
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
            'metode_bayar' => 'required|in:CASH,TRANSFER-EDC,SPLIT,DP',
            'total_bayar' => 'required|numeric|min:0',
            'yangdibayar' => 'nullable|numeric|min:0',
            'ongkir' => 'nullable|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'status_penjualan' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.metode_bayar' => 'required|string',
            'details.*.bank_asal' => 'nullable|string',
            'details.*.bank_tujuan' => 'nullable|string',
            'details.*.ref_pembayaran' => 'nullable|string',
            'details.*.nominal' => 'required|numeric|min:0',
        ]);

        // dd($request);
        return DB::transaction(function () use ($penjualan, $data) {
            $tglBayar = Carbon::parse($data['tanggal_bayar']);
            $totalTagihan = ($data['total_bayar'] + $data['ongkir']) - $data['diskon'];
            $isLunas = $data['yangdibayar'] >= $totalTagihan;
            // 1. Update Ongkir & Diskon di Tabel Penjualan
            $penjualan->update([
                'ongkir' => $data['ongkir'] ?? 0,
                'diskon' => $data['diskon'] ?? 0,
                // 'status' => $isLunas ? 'LUNAS' : 'DP',
                'status' => $data['status_penjualan'] ?? ($isLunas ? 'LUNAS' : 'DP'),
            ]);


            $kembalian = $data['yangdibayar'] - $totalTagihan;
            $kodeBayar = "INVPY" . date('dmYHis') . rand(10, 99);

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
                    'bank_tujuan'      => $detail['bank_tujuan'] ?? 'CASH',
                    'no_ref'           => $detail['ref_pembayaran'] ?? null,
                    'jumlah_bayar'     => $data['total_bayar'],
                    'ongkir'            => $data['ongkir'],
                    'diskon'            => $data['diskon'],
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

    public function showPrint($id)
    {
        // Ambil data penjualan beserta relasinya
        $penjualan = Penjualan::with([
            'items.varian.produk',
            'pelanggan',
            'marketing',
            'items.details.satuan',
            'pembayaran.details',
        ])->findOrFail($id);

        $pembayaranTerakhir = $penjualan->pembayaran()->latest()->first();
        $dddddd = Pembayaran::with(['details', 'penjualan.pelanggan', 'penjualan.marketing'])->where('penjualan_id', $id)->latest()->first();
        // dd($penjualan);
        return Inertia::render('Ecer/Penjualan/Print', [
            'penjualan' => $penjualan,
            'pembayaran' =>  $dddddd,
            'tanggal_indo' => \Carbon\Carbon::parse($pembayaranTerakhir->tanggal)->isoFormat('D MMMM YYYY'),
        ]);
    }
}
