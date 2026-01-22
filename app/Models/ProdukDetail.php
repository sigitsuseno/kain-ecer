<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukDetail extends Model
{
    protected $fillable = [
        'varian_id',
        'stok_id',
        'satuan_id',
        'qty',
        'isi',
        'status',
    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class, 'stok_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
