<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PenjualanDetail>
 */
class PenjualanDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $harga_jual_eceran = $this->faker->numberBetween(15000, 200000);
        $harga_jual_borongan = $this->faker->numberBetween(12000, 180000);
        $jumlah = $this->faker->randomFloat(2, 0.1, 10);
        $diskon = $this->faker->numberBetween(0, 20);
        $harga = $this->faker->randomElement([$harga_jual_eceran, $harga_jual_borongan]);
        $subtotal = ($harga * $jumlah) * (1 - $diskon / 100);

        return [
            'id_penjualan' => \App\Models\Penjualan::factory(),
            'id_produk' => \App\Models\Produk::factory(),
            'harga_jual_eceran' => $harga_jual_eceran,
            'harga_jual_borongan' => $harga_jual_borongan,
            'jumlah' => $jumlah,
            'diskon' => $diskon,
            'subtotal' => round($subtotal),
            'status' => $this->faker->boolean,
        ];
    }
}
