<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_perusahaan' => $this->faker->company,
            'alamat' => $this->faker->address,
            'telepon' => $this->faker->phoneNumber,
            'tipe_nota' => $this->faker->numberBetween(1, 2),
            'path_logo' => $this->faker->imageUrl(),
            'path_kartu_member' => $this->faker->imageUrl(),
            'diskon' => $this->faker->numberBetween(0, 50),
        ];
    }
}
