<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector; // Sesuaikan jenis konektor
use Mike42\Escpos\Printer;

class PrintController extends Controller
{

    public function print(Request $request)
    {
        $data = $request->all();

        try {
            // Nama printer harus sesuai dengan sharing name di Windows
            $connector = new WindowsPrintConnector("POS80");
            $printer = new Printer($connector);

            // --- HEADER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text("TOKO KAIN ECER\n");
            $printer->selectPrintMode();
            $printer->text("Jl. Tekstil Utama No. 88, Semarang\n");
            $printer->text("Telp: 024-12345678\n");
            $printer->text(str_repeat("-", 42) . "\n");

            // --- INFO NOTA ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            // Padding agar titik dua sejajar otomatis
            $printer->text($this->renderLine("Nota", $data['nomer']));
            $printer->text($this->renderLine("Tanggal", date('d/m/Y', strtotime($data['tanggal'] ?? now()))));
            $printer->text($this->renderLine("Plg", $data['pelanggan']['nama'] ?? 'Umum'));
            $printer->text($this->renderLine("Kasir", $data['user']['name'] ?? 'Staff'));
            $printer->text(str_repeat("-", 42) . "\n");

            // --- ITEMS ---
            foreach ($data['items'] as $item) {
                // Nama Produk & Varian (Satu baris saja)
                $printer->setEmphasis(true);
                $namaBarang = strtoupper($item['varian']['produk']['nama'] . " - " . $item['varian']['nama']);
                $printer->text($namaBarang . "\n");
                $printer->setEmphasis(false);

                foreach ($item['details'] as $dtp) {
                    $qtyInfo = $dtp['qty'] . " " . ($dtp['satuan']['inisial'] ?? 'Yrd');
                    $priceInfo = "@" . number_format($dtp['harga']);
                    $subtotal = number_format($dtp['jumlah_harga']);

                    // Layout:  10 Yrd @25.000          250.000
                    $printer->text($this->formatRow($qtyInfo . " " . $priceInfo, $subtotal) . "\n");
                }
            }
            $printer->text(str_repeat("-", 42) . "\n");

            // --- TOTAL ---
            $printer->setEmphasis(true);
            $printer->text($this->formatRow("TOTAL AKHIR", number_format($data['total_penjualan'])) . "\n");
            $printer->setEmphasis(false);

            // --- PEMBAYARAN ---
            $totalBayar = 0;
            foreach ($data['pembayaran'] as $pay) {
                foreach ($pay['details'] as $det) {
                    $nominal = $det['nominal'];
                    $totalBayar += $nominal;
                    $label = "BAYAR (" . ($det['metode_bayar'] ?? 'CASH') . ")";
                    $printer->text($this->formatRow($label, number_format($nominal)) . "\n");
                }
            }

            // --- SISA / KEMBALI ---
            $sisa = $data['total_penjualan'] - $totalBayar;
            if ($sisa > 0) {
                $printer->text($this->formatRow("SISA PIUTANG", number_format($sisa)) . "\n");
            } elseif ($sisa < 0) {
                $printer->text($this->formatRow("KEMBALIAN", number_format(abs($sisa))) . "\n");
            }

            // --- FOOTER ---
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("TERIMA KASIH\n");
            $printer->text("Kain yang sudah dipotong tidak dapat\nditukar atau dikembalikan.\n");
            $printer->text(date('d/m/Y H:i') . "\n");

            $printer->cut();
            $printer->close();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi pembantu agar titik dua sejajar (Info Nota)
    private function renderLine($label, $value)
    {
        return sprintf("%-12s: %s\n", $label, $value);
    }

    // Fungsi pembantu rata kiri-kanan (Items/Total)
    private function formatRow($left, $right, $width = 42)
    {
        $space = $width - strlen($left) - strlen($right);
        return $left . str_repeat(" ", max(0, $space)) . $right;
    }

    // Helper untuk merapikan teks kolom kiri dan kanan
    // private function formatRow($left, $right, $isBold = false)
    // {
    //     $width = 42; // Lebar karakter kertas 80mm biasanya 42-48 karakter
    //     $space = $width - strlen($left) - strlen($right);
    //     return $left . str_repeat(" ", max(0, $space)) . $right;
    // }
}
