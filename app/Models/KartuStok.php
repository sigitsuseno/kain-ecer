<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    protected $guarded = ['id'];

    public function details()
    {
        return $this->belongsTo(ProdukDetail::class, 'produk_detail_id');
    }
}
