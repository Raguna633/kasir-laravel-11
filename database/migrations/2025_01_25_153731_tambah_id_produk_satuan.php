<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TambahIdProdukSatuan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penjualan_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produk_satuan')->after('id_produk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penjualan_detail', function (Blueprint $table) {
            $table->dropColumn('id_produk_satuan');
        });
    }
}
