<?php

namespace App\Http\Controllers\Ecer;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Perusahaan;
use App\Models\ProdukVarian;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class PenjualanEcerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 10);

        $query = Penjualan::query()
            ->with([
                'pelanggan:id,nama',
                'marketing:id,nama',
            ])->where('bagian', 'ecer')->whereIn('status', ['UNPAID', 'DRAFT']);

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

    public function printTagihan($id)
    {
        $penjualan = Penjualan::with(['items.varian.produk', 'items.details', 'pelanggan', 'marketing', 'pembayaran'])
            ->findOrFail($id);

        Carbon::setLocale('id');
        $tgl = Carbon::parse($penjualan->tanggal)->isoFormat('D MMMM YYYY');

        try {
            $connector = new WindowsPrintConnector("POS80");
            $printer = new Printer($connector);
            $width = 46; // Ukuran custom Anda

            // --- HEADER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_EMPHASIZED);
            $printer->text("UTEX\n");
            $printer->selectPrintMode();
            $printer->text("Jl. Musi No.12, RT.05/RW.02\n");
            $printer->text("Sidomulyo, Kec. Ungaran Tim., Kab. Semarang\n");
            $printer->text(str_repeat("-", $width) . "\n");

            // --- INFO TAGIHAN (Lurus dengan Titik Dua) ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($this->formatBarisInformasi("Nota Tagihan", $penjualan->nomer, $width));
            $printer->text($this->formatBarisInformasi("Tanggal", $tgl, $width));
            $printer->text($this->formatBarisInformasi("Pelanggan", $penjualan->pelanggan->nama ?? 'Umum', $width));
            $printer->text($this->formatBarisInformasi("Marketing", $penjualan->marketing->nama ?? '-', $width));
            $printer->text(str_repeat("-", $width) . "\n");

            // --- ITEMS ---
            $currentProduk = "";
            foreach ($penjualan->items as $item) {
                $namaProduk = $item->varian->produk->nama;
                $warna = " " . $item->varian->nama;

                if ($currentProduk != $namaProduk) {
                    $printer->setEmphasis(true);
                    // Nama Produk Kapital
                    $printer->text(strtoupper($namaProduk) . "\n");
                    $printer->setEmphasis(false);
                    $currentProduk = $namaProduk;
                }

                // Sub-header Warna
                $printer->text($warna . "\n");

                foreach ($item->details as $detail) {
                    // Kalkulasi (Rata Kiri dengan indentasi 3 spasi agar rapi)
                    $kalkulasi = "   " . number_format($detail->qty, 2) . " " . $detail->satuan->nama_satuan . " x " . number_format($detail->harga, 0, ',', '.');

                    // Subtotal (Rata Kanan)
                    $subtotal = number_format($detail->jumlah_harga, 0, ',', '.');

                    // Kolom kiri 32 + Kolom kanan 14 = 46
                    $printer->text(sprintf("%-32s%14s\n", $kalkulasi, $subtotal));
                }
            }
            $printer->text(str_repeat("-", $width) . "\n");

            // --- SUMMARY ---
            $printer->setEmphasis(true);
            $printer->text($this->formatRow("TOTAL TAGIHAN", number_format($penjualan->total_penjualan, 0, ',', '.'), $width));
            $printer->setEmphasis(false);

            if ($penjualan->diskon > 0) {
                $printer->text($this->formatRow("POT. DISKON", "-" . number_format($penjualan->diskon, 0, ',', '.'), $width));
            }
            if ($penjualan->pajak > 0) {
                $printer->text($this->formatRow("PAJAK / PPN", number_format($penjualan->pajak, 0, ',', '.'), $width));
            }
            if ($penjualan->ongkir > 0) {
                $printer->text($this->formatRow("BIAYA ONGKIR", number_format($penjualan->ongkir, 0, ',', '.'), $width));
            }
            if ($penjualan->has_retur) {
                $printer->text($this->formatRow("RETUR BARANG", "ADA", $width));
            }

            $printer->text(str_repeat("-", $width) . "\n");

            // --- PEMBAYARAN ---
            $totalBayar = $penjualan->pembayaran->sum('nominal');
            $printer->text($this->formatRow("TOTAL DIBAYAR", number_format($totalBayar, 0, ',', '.'), $width));

            $sisa = $penjualan->total_penjualan - $totalBayar;
            if ($sisa > 0) {
                $printer->setEmphasis(true);
                $printer->text($this->formatRow("SISA TAGIHAN", "Rp " . number_format($sisa, 0, ',', '.'), $width));
                $printer->setEmphasis(false);
            } else {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->text("--- LUNAS ---\n");
            }

            // --- FOOTER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("\n*** TERIMA KASIH ***\n");
            $printer->text("Barang yang sudah di potong / dibeli\n");
            $printer->text("Tidak dapat dikembalikan / ditukar.\n");
            $printer->text(date('d/m/Y H:i:s') . "\n");

            $printer->cut();
            $printer->close();

            return back();
        } catch (\Exception $e) {
            return back()->with('error', "Gagal: " . $e->getMessage());
        }
    }

    // Helper untuk Info (Titik dua sejajar vertikal)
    private function formatBarisInformasi($label, $isi, $lebarTotal = 46)
    {
        $lebarLabel = 14;
        $lebarTitikDua = 2;
        $lebarIsi = $lebarTotal - $lebarLabel - $lebarTitikDua;
        return sprintf("%-{$lebarLabel}s%-{$lebarTitikDua}s%s\n", $label, ":", $isi);
    }

    // Helper untuk Summary (Label kiri, Angka kanan)
    private function formatRow($left, $right, $width)
    {
        $leftWidth = 20;
        $rightWidth = $width - $leftWidth;
        return sprintf("%-{$leftWidth}s%{$rightWidth}s\n", $left, $right);
    }
}
