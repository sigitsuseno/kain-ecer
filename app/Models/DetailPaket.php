<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPaket extends Model
{
    protected $fillable = [
        'induk_varian_id',
        'anak_varian_id',
        'satuan_id',
        'qty',
        'isi_2',
        'status',
    ];

    public function produkVarian()
    {
        return $this->belongsTo(ProdukVarian::class, 'induk_varian_id');
    }
    public function itemsDetails()
    {
        return $this->belongsTo(StokFisik::class, 'anak_varian_id');
    }
}
