<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProdukSatuan>
 */
class ProdukSatuanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_produk' => \App\Models\Produk::factory(),
            'id_satuan' => \App\Models\SatuanProduk::factory(),
            'harga_jual_eceran' => $this->faker->numberBetween(15000, 200000),
            'harga_jual_borongan' => $this->faker->numberBetween(12000, 180000),
        ];
    }
}
