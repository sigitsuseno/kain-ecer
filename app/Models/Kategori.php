<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'nama_kategori',
        'slug',
        'status',
        'deskripsi',
    ];

    public function produks()
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    /**
     * Mendapatkan semua produk berdasarkan kategori warna
     */
    public function warnaProduk()
    {
        return $this->hasMany(Produk::class, 'warna_id');
    }
}
