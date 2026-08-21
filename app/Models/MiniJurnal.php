<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MiniJurnal extends Model
{
    protected $fillable = [
        'no_jurnal',
        'tanggal',
        'keterangan',
        'jurnalable_type',
        'jurnalable_id',
        'pembayaran_id',
        'pelanggan_id',
        'total_amount'
    ];

    // Relasi ke Detail Item
    public function items(): HasMany
    {
        return $this->hasMany(MiniJurnalItem::class);
    }

    // Relasi Polymorphic (Bisa ke Pembayaran, Retur, atau Penjualan)
    public function jurnalable(): MorphTo
    {
        return $this->morphTo();
    }

    // Relasi ke Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }
}
