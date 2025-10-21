<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produk>
 */
class ProdukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_kategori' => \App\Models\Kategori::factory(),
            'kode_produk' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'nama_produk' => $this->faker->unique()->word,
            'merk' => $this->faker->company,
            'harga_beli' => $this->faker->numberBetween(10000, 100000),
            'diskon' => $this->faker->numberBetween(0, 50),
            'stok' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
