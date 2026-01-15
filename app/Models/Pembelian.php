<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $fillable = [
        'supplier_id',
        'nomer_pembelian',
        'tanggal_transaksi',
        'status',
        'total_roll',
        'total_panjang',
        'catatan',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class . 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PembelianItems::class, 'pembelian_id');
    }
}
