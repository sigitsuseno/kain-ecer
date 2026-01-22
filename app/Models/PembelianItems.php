<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianItems extends Model
{
    protected $fillable = [
        'pembelian_id',
        'varian_id',
        'satuan_id',
        'total_qty',
        'total_jumlah',
        'harga_beli',
        'subtotal',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function produk()
    {
        return $this->hasOne(Produk::class, 'produk_id');
    }

    public function details()
    {
        return $this->hasMany(PembelianItemsDetails::class, 'pembelian_item_id');
    }

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }
}
