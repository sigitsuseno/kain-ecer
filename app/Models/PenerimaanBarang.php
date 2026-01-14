<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanBarang extends Model
{
    protected $table = 'penerimaan_barangs';

    protected $fillable = [
        'penerimaan_id',
        'produk_id',
        'jumlah_roll',
        'jumlah_panjang',
        'harga_satuan',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class, 'penerimaan_id');
    }
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
    public function getTotalHargaAttribute()
    {
        return $this->jumlah_panjang * $this->harga_satuan;
    }
    public function rollTerima()
    {
        return $this->hasMany(Roll::class, 'item_pembelian_id');
    }
}
