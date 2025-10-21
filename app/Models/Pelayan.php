<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelayan extends Model
{
    use HasFactory;

    protected $table = 'pelayan';
    protected $guarded = [];

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_pelayan', 'id');
    }
}
