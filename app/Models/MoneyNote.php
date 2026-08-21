<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyNote extends Model
{
    protected $guarded = ['id'];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }
}
