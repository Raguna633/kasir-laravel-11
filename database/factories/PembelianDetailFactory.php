<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PembelianDetail>
 */
class PembelianDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $harga_beli = $this->faker->numberBetween(10000, 100000);
        $jumlah = $this->faker->numberBetween(1, 20);
        $subtotal = $harga_beli * $jumlah;

        return [
            'id_pembelian' => \App\Models\Pembelian::factory(),
            'id_produk' => \App\Models\Produk::factory(),
            'harga_beli' => $harga_beli,
            'jumlah' => $jumlah,
            'subtotal' => $subtotal,
        ];
    }
}
