<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianItems extends Model
{
    protected $fillable = [
        'pembelian_id',
        'produk_id',
        'panjang_perroll',
        'jumlah_roll',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function produk()
    {
        return $this->hasOne(Produk::class, 'produk_id');
    }
}
