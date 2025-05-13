<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProdukSatuan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produk_satuan', function (Blueprint $table) {
            $table->id();                                              
            $table->foreignId('id_produk')
                  ->references('id_produk')->on('produk')
                  ->onDelete('cascade');
            $table->foreignId('id_satuan')
                  ->references('id')->on('satuan_produk')
                  ->onDelete('cascade');
            $table->integer('harga_jual_eceran')->default(0);
            $table->integer('harga_jual_borongan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}