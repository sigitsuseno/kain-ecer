<?php

namespace App\Observers;

use App\Models\Produk;
use App\Models\RollPerKodewarna;

class ProdukObserver
{
    public function saved(Produk $produk): void
    {
        $this->syncSummary($produk->kode_warna, $produk->gudang_id);
    }

    // Terpanggil setiap ada Data Dihapus
    public function deleted(Produk $produk): void
    {
        $this->syncSummary($produk->kode_warna, $produk->gudang_id);
    }

    /**
     * Logic Utama untuk Update Tabel Bantuan
     */
    private function syncSummary($kodeWarna, $gudangId)
    {
        // 1. Ambil semua roll dengan warna & gudang yang sama
        $allRolls = Produk::where('kode_warna', $kodeWarna)
            ->where('gudang_id', $gudangId)
            ->get();

        // 2. Jika data masih ada, update/create summary
        if ($allRolls->count() > 0) {
            RollPerKodewarna::updateOrCreate(
                [
                    'kode_warna' => $kodeWarna,
                    'gudang_id'  => $gudangId
                ],
                [
                    'nama_produk'     => $allRolls->first()->nama_produk,
                    'total_roll'      => $allRolls->count(),
                    'total_panjang'   => $allRolls->sum('panjang_perroll'),
                    'rincian_panjang' => $allRolls->pluck('panjang_perroll')->implode(', ')
                ]
            );
        } else {
            // 3. Jika roll terakhir dihapus, hapus juga baris di tabel summary
            RollPerKodewarna::where('kode_warna', $kodeWarna)
                ->where('gudang_id', $gudangId)
                ->delete();
        }
    }
}
