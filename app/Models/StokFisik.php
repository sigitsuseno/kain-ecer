<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokFisik extends Model
{
    protected $table = 'stok_fisiks';

    protected $fillable = [
        'varian_id',
        'gudang_id',
        'nomor_batch',
        'tanggal_kadaluarsa',
        'total_panjang',
        'total_qty',
        'harga_jual',
        'harga_beli',
    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function detailPakets()
    {
        return $this->HasMany(DetailPaket::class, 'anak_varian_id');
    }
}
