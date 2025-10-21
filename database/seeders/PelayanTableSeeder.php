<?php

namespace Database\Seeders;

use App\Models\Pelayan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PelayanTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pelayan = [
            [
                'nama' => 'Ahmad Surya',
                'poin' => 150,
            ],
            [
                'nama' => 'Siti Nurhaliza',
                'poin' => 200,
            ],
            [
                'nama' => 'Budi Santoso',
                'poin' => 175,
            ],
            [
                'nama' => 'Maya Sari',
                'poin' => 120,
            ],
            [
                'nama' => 'Rudi Hartono',
                'poin' => 180,
            ],
            [
                'nama' => 'Dewi Lestari',
                'poin' => 160,
            ],
            [
                'nama' => 'Agus Setiawan',
                'poin' => 190,
            ],
            [
                'nama' => 'Rina Amelia',
                'poin' => 140,
            ],
            [
                'nama' => 'Hendra Gunawan',
                'poin' => 170,
            ],
            [
                'nama' => 'Lisa Permata',
                'poin' => 130,
            ],
        ];

        foreach ($pelayan as $data) {
            Pelayan::create($data);
        }
    }
}
