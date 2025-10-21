<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pembelian>
 */
class PembelianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_supplier' => \App\Models\Supplier::factory(),
            'total_item' => $this->faker->numberBetween(1, 50),
            'total_harga' => $this->faker->numberBetween(100000, 5000000),
            'diskon' => $this->faker->numberBetween(0, 20),
            'bayar' => $this->faker->numberBetween(100000, 5000000),
        ];
    }
}
