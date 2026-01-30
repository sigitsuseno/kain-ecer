<?php

namespace App\Services;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class PrinterService
{
    /**
     * Fungsi utama untuk cetak nota
     */
    public function cetakNota($penjualan)
    {
        // Hubungkan ke printer yang di-share di Windows
        // Ganti "POS-80" dengan nama sharing printer Anda
        $connector = new WindowsPrintConnector("POS80");
        $printer = new Printer($connector);

        try {
            /* 1. HEADER */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT);
            $printer->text("UTEX\n");
            $printer->selectPrintMode(); // Reset ke normal
            $printer->text("Jl. Musi No.12, RT.05/RW.02\n");
            $printer->text("Sidomulyo, Ungaran Timur\n");
            $printer->text(str_repeat("-", 48) . "\n");

            /* 2. INFO TRANSAKSI */
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($this->buatBarisDuaKolom("Nota", ": " . $penjualan->nomer));
            $printer->text($this->buatBarisDuaKolom("Tgl", ": " . $penjualan->tanggal));
            $printer->text($this->buatBarisDuaKolom("Plg", ": " . ($penjualan->pelanggan->nama ?? 'Umum')));
            $printer->text($this->buatBarisDuaKolom("Kasir", ": " . ($penjualan->user->name ?? 'Admin')));
            $printer->text(str_repeat("-", 48) . "\n");

            /* 3. ITEM PERULANGAN */
            foreach ($penjualan->items as $item) {
                // Nama Varian Produk (Bold)
                $printer->setEmphasis(true);
                $nama = ($item->varian->produk->nama ?? '') . " " . ($item->varian->nama ?? '');
                $printer->text(strtoupper($nama) . "\n");
                $printer->setEmphasis(false);

                // Detail Qty dan Harga
                foreach ($item->details as $det) {
                    $qtyText = number_format($det->qty, 2) . " " . ($det->satuan->inisial ?? '');
                    $hargaText = number_format($det->harga, 0, ',', '.');
                    $subtotalText = number_format($det->jumlah_harga, 0, ',', '.');

                    // Format: "  127.00 Yd x 10.000         1.270.000"
                    $kiri = "  " . $qtyText . " x " . $hargaText;
                    $printer->text($this->buatBarisDuaKolom($kiri, $subtotalText));
                }
            }
            $printer->text(str_repeat("-", 48) . "\n");

            /* 4. TOTAL & STATUS BAYAR */
            $total = number_format($penjualan->total_penjualan, 0, ',', '.');

            $printer->setEmphasis(true);
            $printer->text($this->buatBarisDuaKolom("TOTAL TAGIHAN", $total));
            $printer->setEmphasis(false);

            $printer->text(str_repeat("-", 48) . "\n");

            // Logika Bayar (Bisa Anda sesuaikan dengan data pembayaran)
            $printer->text($this->buatBarisDuaKolom("BAYAR (CASH)", "0"));

            $printer->setEmphasis(true);
            $printer->text($this->buatBarisDuaKolom("SISA PIUTANG", $total));
            $printer->setEmphasis(false);

            /* 5. FOOTER */
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("*** TERIMA KASIH ***\n");
            $printer->text("BARANG YANG SUDAH DIBELI\n");
            $printer->text("TIDAK DAPAT DITUKAR/DIKEMBALIKAN.\n");

            // Perintah Potong Kertas
            $printer->cut();
        } finally {
            $printer->close();
        }
    }

    /**
     * Helper untuk membuat teks rata kiri dan rata kanan dalam satu baris
     */
    private function buatBarisDuaKolom($kiri, $kanan, $lebarMaks = 48)
    {
        $sisaSpasi = $lebarMaks - strlen($kiri) - strlen($kanan);
        if ($sisaSpasi < 0) $sisaSpasi = 1;
        return $kiri . str_repeat(" ", $sisaSpasi) . $kanan . "\n";
    }
}
