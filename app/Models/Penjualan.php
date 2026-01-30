<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $fillable = [
        'nomer',
        'tanggal',
        'bagian',
        'pelanggan_id',
        'marketing_id',
        'total_penjualan',
        'diskon',
        'pajak',
        'ongkir',
        'kembalian',
        'status',
        'has_retur',
    ];

    public function items()
    {
        return $this->hasMany(PenjualanItem::class, 'penjualan_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'penjualan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function marketing()
    {
        return $this->belongsTo(Supplier::class, 'marketing_id');
    }
}
