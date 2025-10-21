<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'kode_member' => 'MEM001',
                'nama' => 'Ahmad Rahman',
                'telepon' => '081234567890',
                'alamat' => 'Jl. Sudirman No. 1, Jakarta Pusat',
            ],
            [
                'kode_member' => 'MEM002',
                'nama' => 'Siti Nurhaliza',
                'telepon' => '081234567891',
                'alamat' => 'Jl. Thamrin No. 2, Jakarta Pusat',
            ],
            [
                'kode_member' => 'MEM003',
                'nama' => 'Budi Santoso',
                'telepon' => '081234567892',
                'alamat' => 'Jl. Gatot Subroto No. 3, Jakarta Selatan',
            ],
            [
                'kode_member' => 'MEM004',
                'nama' => 'Maya Sari',
                'telepon' => '081234567893',
                'alamat' => 'Jl. MH Thamrin No. 4, Jakarta Pusat',
            ],
            [
                'kode_member' => 'MEM005',
                'nama' => 'Rudi Hartono',
                'telepon' => '081234567894',
                'alamat' => 'Jl. Jenderal Sudirman No. 5, Jakarta Pusat',
            ],
            [
                'kode_member' => 'MEM006',
                'nama' => 'Dewi Lestari',
                'telepon' => '081234567895',
                'alamat' => 'Jl. Asia Afrika No. 6, Bandung',
            ],
            [
                'kode_member' => 'MEM007',
                'nama' => 'Agus Setiawan',
                'telepon' => '081234567896',
                'alamat' => 'Jl. Braga No. 7, Bandung',
            ],
            [
                'kode_member' => 'MEM008',
                'nama' => 'Rina Amelia',
                'telepon' => '081234567897',
                'alamat' => 'Jl. Dago No. 8, Bandung',
            ],
            [
                'kode_member' => 'MEM009',
                'nama' => 'Hendra Gunawan',
                'telepon' => '081234567898',
                'alamat' => 'Jl. Cihampelas No. 9, Bandung',
            ],
            [
                'kode_member' => 'MEM010',
                'nama' => 'Lisa Permata',
                'telepon' => '081234567899',
                'alamat' => 'Jl. Setiabudi No. 10, Bandung',
            ],
            [
                'kode_member' => 'MEM011',
                'nama' => 'Dedi Kurniawan',
                'telepon' => '081234567800',
                'alamat' => 'Jl. Pahlawan No. 11, Surabaya',
            ],
            [
                'kode_member' => 'MEM012',
                'nama' => 'Nina Marlina',
                'telepon' => '081234567801',
                'alamat' => 'Jl. Tunjungan No. 12, Surabaya',
            ],
            [
                'kode_member' => 'MEM013',
                'nama' => 'Fajar Prasetyo',
                'telepon' => '081234567802',
                'alamat' => 'Jl. Basuki Rahmat No. 13, Surabaya',
            ],
            [
                'kode_member' => 'MEM014',
                'nama' => 'Gita Kirana',
                'telepon' => '081234567803',
                'alamat' => 'Jl. Darmo No. 14, Surabaya',
            ],
            [
                'kode_member' => 'MEM015',
                'nama' => 'Hadi Susanto',
                'telepon' => '081234567804',
                'alamat' => 'Jl. Raya Darmo No. 15, Surabaya',
            ],
            [
                'kode_member' => 'MEM016',
                'nama' => 'Intan Permadi',
                'telepon' => '081234567805',
                'alamat' => 'Jl. Malioboro No. 16, Yogyakarta',
            ],
            [
                'kode_member' => 'MEM017',
                'nama' => 'Joko Widodo',
                'telepon' => '081234567806',
                'alamat' => 'Jl. Prawirotaman No. 17, Yogyakarta',
            ],
            [
                'kode_member' => 'MEM018',
                'nama' => 'Kartika Sari',
                'telepon' => '081234567807',
                'alamat' => 'Jl. Sosrowijayan No. 18, Yogyakarta',
            ],
            [
                'kode_member' => 'MEM019',
                'nama' => 'Lutfi Hakim',
                'telepon' => '081234567808',
                'alamat' => 'Jl. Affandi No. 19, Yogyakarta',
            ],
            [
                'kode_member' => 'MEM020',
                'nama' => 'Mega Putri',
                'telepon' => '081234567809',
                'alamat' => 'Jl. Parangtritis No. 20, Yogyakarta',
            ],
        ];

        foreach ($members as $member) {
            Member::create($member);
        }
    }
}
