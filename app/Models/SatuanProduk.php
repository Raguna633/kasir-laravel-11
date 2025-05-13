<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatuanProduk extends Model
{
    protected $table = 'satuan_produk';
    protected $fillable = ['nama'];

    public function masterSatuan()
    {
        return $this->belongsTo(SatuanProduk::class, 'id_satuan');
    }

    public function produk()
    {
        return $this->belongsToMany(
            Produk::class,
            'produk_satuan',
            'id_produk',
            'id_satuan'
        )->withPivot('harga_jual_eceran','harga_jual_borongan');
    }
}