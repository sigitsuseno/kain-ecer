<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $fillable = [
        'supplier_id',
        'nomer',
        'tanggal',
        'total_pembelian',
        'beban_ongkir',
        'beban_biaya',
        'status',
        'catatan',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PembelianItems::class, 'pembelian_id');
    }
}
