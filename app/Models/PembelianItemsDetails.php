<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianItemsDetails extends Model
{
    protected $table = 'pembelian_items_details';
    protected $fillable = [
        'pembelian_item_id',
        'produk_detail_id',
        'satuan_id',
        'qty',
        'lot_number',
    ];

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItems::class, 'pembelian_item_id');
    }
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
