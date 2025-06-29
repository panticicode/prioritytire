<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->delete();

        $admin = User::create([
            'is_admin' => true,
            'name'     => 'Admin',
            'email'    => 'admin@prioritytire.local',
            'password' => bcrypt('123')
        ]);
    }
}
