<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukVarian extends Model
{
    protected $fillable = [
        'produk_id',
        'gudang_id',
        'nama',
        'tipe',
        'panjang',
        'tinggi',
        'lebar',
        'tebal',
        'sku',
        'is_paket',
        'harga_beli',
        'harga_jual',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
    public function stok()
    {
        return $this->hasOne(Stok::class, 'varian_id');
    }

    public function details()
    {
        return $this->hasMany(ProdukDetail::class, 'varian_id');
    }
    public function konversi()
    {
        return $this->hasMany(KonversiSatuan::class, 'satuan_id');
    }
    public function purcItem()
    {
        return $this->hasOne(PembelianItems::class, 'varian_id');
    }
}
