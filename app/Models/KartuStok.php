<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    protected $guarded = ['id'];

    public function detail()
    {
        return $this->belongsTo(ProdukDetail::class, 'produk_detail_id');
    }
}
