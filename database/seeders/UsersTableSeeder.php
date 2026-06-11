<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------
        // ADMIN (1)
        // -------------------------
        User::updateOrCreate([
            'email' => 'amer.alqadri@email.com',
        ], [
            'first_name' => 'amer',
            'last_name' => 'alqadri',
            'phone' => '0999000001',
            'date_of_birth' => '1985-06-12',
            'address' => 'Damascus, Syria',
            'balances' => ['USD' => 1200, 'SYP' => 1500000],
            'password' => Hash::make('Admin1234'),
            'role' => 'admin',
            'user_category' => 'public',   // 🔥 مهم جداً
            'profile_image' => 'profile_images/amer.jpg',
            'status' => 'approved',  
        ]);

        // -------------------------
        // SUB ADMINS (1)
        // -------------------------
        User::updateOrCreate([
            'email' => 'ahmad.zen@email.com',
        ], [
            'first_name' => 'ahmad',
            'last_name' => 'zen',
            'phone' => '0999000002',
            'date_of_birth' => '1990-02-20',
            'address' => 'Homs, Syria',
            'national_id' => 'SY0987654321',
            'international_passport' => 'P87654321',
            'balances' => ['USD' => 800, 'EUR' => 300],
            'password' => Hash::make('SubAdmin1234'),
            'role' => 'sub_admin',
            'user_category' => 'public',   // 🔥
            'profile_image' => 'profile_images/ahmad.jpg',
            'status' => 'approved',
        ]);

        // -------------------------
        // NORMAL USERS (4)
        // -------------------------
        User::updateOrCreate([
            'email' => 'milad.hamad@example.com',
        ], [
            'first_name' => 'milad',
            'last_name' => 'hamad',
            'phone' => '0999000003',
            'date_of_birth' => '1998-11-05',
            'address' => 'Aleppo, Syria',
            'national_id' => 'SY1122334455',
            'international_passport' => 'P11223344',
            'balances' => ['USD' => 150, 'SAR' => 500],
            'password' => Hash::make('User1234'),
            'role' => 'user',
            'user_category' => 'public',   // 🔥
            'profile_image' => 'profile_images/milad.jpg',
            'status' => 'approved',
        ]);

        User::updateOrCreate([
            'email' => 'sedra.jlilaty@example.com',
        ], [
            'first_name' => 'sedra',
            'last_name' => 'jlilaty',
            'phone' => '0999000004',
            'date_of_birth' => '1999-07-18',
            'address' => 'Lattakia, Syria',
            'national_id' => 'SY2233445566',
            'international_passport' => 'P22334455',
            'balances' => ['USD' => 200, 'EUR' => 120],
            'password' => Hash::make('User1234'),
            'role' => 'user',
            'user_category' => 'public',   // 🔥
            'profile_image' => 'profile_images/sedra.jpg',
            'status' => 'approved',
        ]);

        User::updateOrCreate([
            'email' => 'marwa.alsaour@example.com',
        ], [
            'first_name' => 'marwa',
            'last_name' => 'alsaour',
            'phone' => '0999000005',
            'date_of_birth' => '2000-03-30',
            'address' => 'Damascus, Syria',
            'national_id' => 'SY3344556677',
            'international_passport' => 'P33445566',
            'balances' => ['USD' => 50, 'SYP' => 420000],
            'password' => Hash::make('User1234'),
            'role' => 'user',
            'user_category' => 'beneficiary',   // 🔥
            'profile_image' => 'profile_images/marwa.jpg',
            'status' => 'approved',
        ]);

        User::updateOrCreate([
            'email' => 'salam.labbad@example.com',
        ], [
            'first_name' => 'salam',
            'last_name' => 'labbad',
            'phone' => '0999000006',
            'date_of_birth' => '1997-09-22',
            'address' => 'Hama, Syria',
            'national_id' => 'SY4455667788',
            'international_passport' => 'P44556677',
            'balances' => ['USD' => 100, 'AED' => 250],
            'password' => Hash::make('User1234'),
            'role' => 'user',
            'user_category' => 'beneficiary',   // 🔥
            'profile_image' => 'profile_images/salam.jpg',
            'status' => 'approved',
        ]);

        // -------------------------
        // BENEFICIARY EXAMPLE (اختياري)
        // -------------------------
        User::updateOrCreate([
            'email' => 'beneficiary@example.com',
        ], [
            'first_name' => 'beneficiary',
            'last_name' => 'example',
            'phone' => '0999000007',
            'date_of_birth' => '2001-05-12',
            'address' => 'Homs, Syria',
            'national_id' => 'SY5566778899',
            'international_passport' => 'P55667788',
            'balances' => ['USD' => 20, 'SYP' => 150000],
            'password' => Hash::make('Beneficiary1234'),
            'role' => 'user',
            'user_category' => 'beneficiary',   // 🔥 مستفيد
            'profile_image' => 'profile_images/beneficiary.jpg',
            'status' => 'approved',
        ]);
    }
}
