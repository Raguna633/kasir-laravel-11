<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    // public $incrementing = true; // Jika primary key auto increment
    // protected $keyType = 'int'; // Tipe data primary key
    protected $guarded = [];
}
