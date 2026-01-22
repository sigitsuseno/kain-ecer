<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $fillable = [
        'penjualan_id',
        'tanggal',
        'kode',
        'total',
        'status',
        'keterangan',
        'is_dp',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function details()
    {
        return $this->hasMany(PembayaranDetails::class, 'pembayaran_id');
    }
}
