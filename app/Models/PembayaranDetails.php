<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranDetails extends Model
{
    protected $fillable = [
        'pembayaran_id',
        'metode_bayar',
        'bank_asal',
        'akun_asal',
        'bank_tujuan',
        'no_ref',
        'nominal',
        'jumlah_bayar',
        'ongkir',
        'diskon',
        'pajak',
        'kembalian',
        'status',
    ];

    public function pembayaran()
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }
}
