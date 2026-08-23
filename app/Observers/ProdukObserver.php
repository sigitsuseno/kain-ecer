<?php

namespace App\Observers;

use App\Models\Produk;

class ProdukObserver
{
    public function saved(Produk $produk): void
    {
        // noop: legacy RollPerKodewarna summary disabled after schema refactor
        // Old columns kode_warna/gudang_id no longer exist on produks table.
    }

    public function deleted(Produk $produk): void
    {
        // noop
    }
}
