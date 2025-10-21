<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Penjualan>
 */
class PenjualanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_member' => $this->faker->optional()->numberBetween(1, 10),
            'total_item' => $this->faker->numberBetween(1, 20),
            'total_harga' => $this->faker->numberBetween(50000, 2000000),
            'diskon' => $this->faker->numberBetween(0, 20),
            'bayar' => $this->faker->numberBetween(50000, 2000000),
            'diterima' => $this->faker->numberBetween(50000, 2000000),
            'id_user' => \App\Models\User::factory(),
            'hutang' => $this->faker->numberBetween(0, 500000),
            'status' => $this->faker->boolean,
            'struk' => null,
            'ishutang' => $this->faker->boolean,
            'tipe_pembeli' => $this->faker->randomElement(['member', 'umum']),
            'nama_pembeli' => $this->faker->name,
            'id_pelayan' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }
}
