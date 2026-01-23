<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $fillable = [
        'penjualan_item_id',
        'satuan_id',
        'qty',
        'harga',
        'jumlah_harga',
        'status',
    ];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function item()
    {
        return $this->belongsTo(PenjualanItem::class, 'penjualan_item_id');
    }
}
