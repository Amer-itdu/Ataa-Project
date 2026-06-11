<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Volunteer;

class VolunteersTableSeeder extends Seeder
{
    public function run(): void
    {
        Volunteer::updateOrCreate(
            ['user_id' => 1],
            [
                'skills' => 'Communication, Teamwork',
                'description' => 'Active volunteer with strong motivation.',
                'status' => 'active',
            ]
        );

        // مثال ثاني بدون Loop
        Volunteer::updateOrCreate(
            ['user_id' => 2],
            [
                'skills' => 'Leadership, Organizing',
                'description' => 'Experienced volunteer.',
                'status' => 'active',
            ]
        );
    }
}
