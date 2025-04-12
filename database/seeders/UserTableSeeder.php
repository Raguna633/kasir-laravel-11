<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = array(
            [
                'name' => 'Administrator Test',
                'email' => 'admintest@gmail.com',
                'password' => bcrypt('GTestAdmin19'),
                'foto' => '/img/pplg.png',
                'level' => 1
            ],
            [
                'name' => 'Kasir Test',
                'email' => 'kasirtest@gmail.com',
                'password' => bcrypt('GTestKasir91'),
                'foto' => '/img/pplg.png',
                'level' => 2
            ]
        );

        array_map(function (array $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }, $users);
    }
}
