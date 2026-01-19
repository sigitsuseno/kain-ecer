<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukVarian extends Model
{
    protected $fillable = [
        'produk_id',
        'gudang_id',
        'nama_varian',
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
    public function stokFisiks()
    {
        return $this->hasMany(StokFisik::class, 'varian_id');
    }
    public function detailIsiPaket()
    {
        return $this->hasMany(DetailPaket::class, 'induk_varian_id');
    }
    public function konversi()
    {
        return $this->hasMany(KonversiSatuan::class, 'satuan_id');
    }
    public function pembelianItem()
    {
        return $this->hasOne(PembelianItems::class, 'varian_id');
    }
}
