<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $fillable = [
        'nama_produk',
        'kategori_id',
        'satuan_id',
        'brand_id',
        'deskripsi',
    ];
    public function varian()
    {
        return $this->hasMany(ProdukVarian::class, 'produk_id');
    }

    public function stokFisik()
    {
        return $this->hasManyThrough(
            StokFisik::class,
            ProdukVarian::class,
            'produk_id',
            'varian_id',
            'id',
            'id'
        );
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
