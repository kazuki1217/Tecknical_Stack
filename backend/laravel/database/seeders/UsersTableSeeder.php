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
                'email_verified_at' => null,
                'password' => Hash::make("sample{$i}pass"),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insert($users);
    }
}
