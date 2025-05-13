<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdukSatuan extends Pivot
{
    protected $table = 'produk_satuan';
    public $timestamps = true;

    public function masterSatuan()
    {
        return $this->belongsTo(SatuanProduk::class, 'id_satuan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }
}