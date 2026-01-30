<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stoks';

    protected $fillable = [
        'varian_id',
        'gudang_id',
        'total_qty',
        'total_unit',
        'nomer',
        'tanggal_kadaluarsa',
    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function details()
    {
        return $this->hasMany(ProdukDetail::class, 'stok_id');
    }
}
