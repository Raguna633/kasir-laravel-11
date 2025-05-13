<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    protected $guarded = [];



    public function satuan()
    {
        return $this->belongsToMany(
            SatuanProduk::class,
            'produk_satuan',
            'id_produk',
            'id_satuan'
        )->withPivot('harga_jual_eceran', 'harga_jual_borongan');
    }

    public function produkSatuan()
    {
        return $this->hasMany(ProdukSatuan::class, 'id_produk');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class,  'id_kategori');
    }
}
