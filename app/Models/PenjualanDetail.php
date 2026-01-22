<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $fillable = [
        'penjualan_item_id',
        'satuan_id',
        'qty',
        'status',
    ];
}
