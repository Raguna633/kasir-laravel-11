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
            $table->unsignedInteger('id_produk');
            $table->string('satuan');
            $table->integer('harga_jual_eceran')->default(0)->nullable();
            $table->integer('harga_jual_borongan')->default(0)->nullable();
            $table->timestamps();

            $table->foreign('id_produk')->references('id_produk')->on('produk')->onDelete('cascade');
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
