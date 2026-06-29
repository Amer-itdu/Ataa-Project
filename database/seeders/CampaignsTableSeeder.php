<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignsTableSeeder extends Seeder
{
    public function run(): void
    {
        // 🔥 1) حملة تبرع فقط
        Campaign::create([
            'user_id'            => 1,
            'title'              => 'Clean Water for Every Village',
            'description'        => 'Raise funds to build clean water systems and provide hygiene supplies for families in need.',
            'type'               => 'humanitarian',
            'participation_type' => 'donation_only',
            'amount_needed'      => 15000,
            'amount_collected'   => 4200,
            'volunteers_needed'  => null,
            'volunteers_joined'  => 0,
            'status'             => 'open',
            'start_date'         => '2026-06-01',
            'end_date'           => '2026-07-15',
        ]);

        // 🔥 2) حملة تطوع فقط
        Campaign::create([
            'user_id'            => 1,
            'title'              => 'Community Health Education Drive',
            'description'        => 'Organize volunteer health workshops and awareness sessions across local neighborhoods.',
            'type'               => 'medical',
            'participation_type' => 'volunteer_only',
            'amount_needed'      => null,
            'amount_collected'   => 0,
            'volunteers_needed'  => 25,
            'volunteers_joined'  => 0,
            'status'             => 'open',
            'start_date'         => '2026-06-10',
            'end_date'           => '2026-08-01',
        ]);

        // 🔥 3) حملة الاثنين معاً (تبرع + تطوع)
        Campaign::create([
            'user_id'            => 1,
            'title'              => 'School Supplies for Orphans',
            'description'        => 'Support orphaned students by providing backpacks, books, and stationery for the new school year.',
            'type'               => 'educational',
            'participation_type' => 'donation_and_volunteer',
            'amount_needed'      => 8000,
            'amount_collected'   => 1850,
            'volunteers_needed'  => 10,
            'volunteers_joined'  => 0,
            'status'             => 'open',
            'start_date'         => '2026-07-01',
            'end_date'           => '2026-09-01',
        ]);

        // 🔥 4) حملة الاثنين معاً
        Campaign::create([
            'user_id'            => 1,
            'title'              => 'Food Baskets for Families',
            'description'        => 'Collect donations and volunteer time to distribute food baskets to struggling families.',
            'type'               => 'humanitarian',
            'participation_type' => 'donation_and_volunteer',
            'amount_needed'      => 12000,
            'amount_collected'   => 7200,
            'volunteers_needed'  => 15,
            'volunteers_joined'  => 0,
            'status'             => 'open',
            'start_date'         => '2026-05-20',
            'end_date'           => '2026-08-20',
        ]);

        // 🔥 5) حملة تطوع فقط
        Campaign::create([
            'user_id'            => 1,
            'title'              => 'Winter Clothing Drive',
            'description'        => 'Gather coats, blankets, and warm clothing for families preparing for winter.',
            'type'               => 'humanitarian',
            'participation_type' => 'volunteer_only',
            'amount_needed'      => null,
            'amount_collected'   => 0,
            'volunteers_needed'  => 20,
            'volunteers_joined'  => 0,
            'status'             => 'open',
            'start_date'         => '2026-10-01',
            'end_date'           => '2026-12-15',
        ]);
    }
}