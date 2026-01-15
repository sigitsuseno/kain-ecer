<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $fillable = [
        'gambar',
        'kode_produk',
        'nama_produk',
        'kode_warna',
        'slug',
        'lebar_kain',
        'tipe',
        'satuan',
        'panjang_perroll',
        'harga_jual_ecer',
        'harga_jual_grosir',
        'harga_beli',
        'kategori_id',
        'supplier_id',
        'warna_id',
        'gudang_id',
        'mode',
        'status',
        'deskripsi',
        'catatan',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
    public function warna()
    {
        return $this->belongsTo(Kategori::class, 'warna_id');
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
