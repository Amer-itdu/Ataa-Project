<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'date_of_birth' => '1990-01-01',
            'profile_image' => 'default.jpg',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'first_name' => 'Sub Admin',
            'last_name' => 'User',
            'email' => 'subadmin@example.com',
            'phone' => '0987654321',
            'password' => Hash::make('password'),
            'date_of_birth' => '1992-05-15',
            'profile_image' => 'default.jpg',
            'role' => 'sub_admin',
            'email_verified_at' => now(),
        ]);
    }
}
