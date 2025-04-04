<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('setting')->insert([
            'id_setting' => 1,
            'nama_perusahaan' => 'Toko Altie',
            'alamat' => 'Jl. Raya Bandung No.03, Bojong, Kec. Karangtengah, Kabupaten Cianjur',
            'telepon' => '081234779987',
            'tipe_nota' => 1, // kecil
            'diskon' => 5,
            'path_logo' => '/img/tefatie.png',
            'path_kartu_member' => '/img/member.png',
        ]);
    }
}
