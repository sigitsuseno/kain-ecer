<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanItem extends Model
{
    protected $fillable = [
        'penjualan_id',
        'varian_id',
        'satuan_id',
        'total_qty',
        'total_jumlah',
        'harga_satuan',
        'subtotal',
    ];
    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'penjualan_item_id');
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }
}
