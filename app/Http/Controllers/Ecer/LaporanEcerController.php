<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Models\MoneyNote;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\PembayaranDetails;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\PenjualanItem;
use App\Models\Stok;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanEcerController extends Controller
{
    public function index(Request $request)
    {
        $notes = MoneyNote::with('pelanggan')->orderBy('tanggal', 'desc')->get();
        return Inertia::render('Ecer/Laporan/Index', [
            'notes' => $notes,
            'pelanggan' => Pelanggan::orderBy('id')->get(),
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:money_notes,id', // Tambahkan validasi ID
            'tanggal' => 'required|date',
            'jenis' => 'required|string',
            'ket' => 'nullable|string',
            'nominal' => 'required|numeric',
        ]);
        // dd($request);
        if ($request->jenis === 'SALDO') {
            MoneyNote::create([
                'tanggal' => $request->tanggal,
                'jenis' => $request->jenis,
                'pelanggan_id' => $request->ref,
                'ket' => $request->ket,
                'nominal' => $request->nominal,
            ]);

            $pelanggan = Pelanggan::findOrFail($request->ref);
            $pelanggan->increment('saldo', $request->nominal);
        } else {
            MoneyNote::create([
                'tanggal' => $request->tanggal,
                'jenis' => $request->jenis,
                'ket' => $request->ket,
                'nominal' => $request->nominal,
            ]);
        }


        return back();
    }

    public function destroy($id)
    {
        MoneyNote::findOrFail($id)->delete();
        return back();
    }
    public function penjualan(Request $request)
    {

        $tanggal = $request->tanggal ?? now()->format('Y-m-d');
        $penjualan = Penjualan::with([
            'pelanggan',
            'pembayaran' => function ($qq) use ($tanggal) {
                $qq->whereDate('tanggal', $tanggal)->with('details');
            },
            'items.varian.produk',
            'items.details.satuan',
        ])
            ->where('bagian', 'ecer')
            ->whereHas('pembayaran', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->get();

        return inertia('Ecer/Laporan/Penjualan', [
            'laporan' => $penjualan,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }

    public function omset(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');
        $pembayaran = PembayaranDetails::with([
            'pembayaran' => function ($qy) use ($tanggal) {
                $qy->where('tanggal', $tanggal)->with('penjualan.pelanggan');
            }
        ])->get();

        // $omset = $pembayaran->groupBy('bank_tujuan');
        $omset = $pembayaran->groupBy('bank_tujuan')->map(function ($group, $bank) {
            return [
                'bank' => $bank ?: 'CASH', // Jika bank_tujuan null, set sebagai CASH
                'total_nominal' => $group->sum('nominal'), // SUM nominal di sini
                'total_diskon' => $group->sum('diskon'),
                'total_ongkir' => $group->sum('ongkir'),
                'jumlah_transaksi' => $group->count(),
                'details' => $group // Tetap menyertakan detail jika ingin ditampilkan
            ];
        })->values();

        // dd($omset);
        return inertia('Ecer/Laporan/Omset', [
            'laporan' => $omset,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }
    public function laporanCatatan(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');
        $catatan = MoneyNote::with('pelanggan')->get();

        // $omset = $pembayaran->groupBy('bank_tujuan');
        $notes = $catatan->groupBy('jenis');

        // dd($notes);
        return inertia('Ecer/Laporan/Catatan', [
            'laporan' => $notes,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }
    // LaporanEcerController.php

    public function bonus(Request $request)
    {
        // Ambil tanggal mulai dan selesai, default ke hari ini jika kosong
        $start = $request->start ?? now()->format('Y-m-d');
        $end = $request->end ?? now()->format('Y-m-d');

        $penjualan = Penjualan::with(['pelanggan', 'marketing'])
            ->where('bagian', 'ecer')
            ->whereBetween('tanggal', [$start, $end]) // Filter rentang tanggal
            ->get();

        // Grouping berdasarkan nama marketing (atau ID)
        $laporan = $penjualan->groupBy(function ($item) {
            return $item->marketing ? $item->marketing->nama : 'Tanpa Marketing';
        })->map(function ($items, $marketingName) {
            return [
                'marketing' => $marketingName,
                'total_penjualan' => $items->sum('total_penjualan'),
                'jumlah_nota' => $items->count(),
                'transaksi' => $items // Detail transaksi per marketing
            ];
        })->values();

        return inertia('Ecer/Laporan/Bonus', [
            'laporan' => $laporan,
            'filters' => [
                'start' => $start,
                'end' => $end
            ]
        ]);
    }

    public function barang(Request $request)
    {
        // 1. Ambil tanggal dari request atau hari ini
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');

        $laporan = PenjualanItem::query()
            ->whereHas('penjualan', function ($q) use ($tanggal) {
                $q->where('bagian', 'ecer')
                    ->whereHas('pembayaran', function ($qp) use ($tanggal) {
                        $qp->whereDate('tanggal', $tanggal);
                    });
            })
            ->with([
                'penjualan' => function ($qq) {
                    $qq->where('bagian', 'ecer')->with('pembayaran');
                },
                'varian.produk',
                'details.satuan'
            ])
            ->get();
        // dd($laporan);
        return inertia('Ecer/Laporan/Barang', [
            'laporan' => $laporan,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }

    public function laporanStok(Request $request)
    {
        // 1. Ambil tanggal dari request atau hari ini
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');

        // 2. Gunakan whereDate untuk memfilter kolom datetime
        $barangKeluar = Stok::with(['varian.produk.satuan', 'varian.details.satuan'])
            ->get();

        // dd($barangKeluar);
        return inertia('Ecer/Laporan/Stok', [
            'laporan' => $laporan,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }

    public function saldo(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');

        $saldo = Pelanggan::orderBy('id')->where('saldo', '>', 0)->get();

        return inertia('Ecer/Laporan/Saldo', [
            'laporan' => $saldo,
            'filters' => [
                'tanggal' => $tanggal
            ]
        ]);
    }
}