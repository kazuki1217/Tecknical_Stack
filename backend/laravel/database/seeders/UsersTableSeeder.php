<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [];

        for ($i = 1; $i <= 3; $i++) {
            $users[] = [
                'name' => "sample{$i}",
                'email' => "sample{$i}@example.com",
                'password' => Hash::make("sample{$i}pass"),
                'created_at' => now(),
            ];
        }

        DB::table('users')->insert($users);
    }
}
