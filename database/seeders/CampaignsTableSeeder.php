<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class CampaignsTableSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Models\User::where('email', 'amer.alqadri@email.com')->first();

        if (!$user) {
            return;
        }

        // 🔥 استخدم updateOrCreate لمنع التكرار
        Campaign::updateOrCreate(
            ['title' => 'Clean Water for Every Village'],
            [
                'user_id'            => $user->id,
                'description'        => 'Raise funds to build clean water systems and provide hygiene supplies for families in need.',
                'type'               => 'humanitarian',
                'participation_type' => 'donation_only',
                'amount_needed'      => 15000,
                'amount_collected'   => 0,
                'volunteers_needed'  => null,
                'volunteers_joined'  => 0,
                'status'             => 'open',
                'start_date'         => '2026-06-01',
                'end_date'           => '2026-07-15',
            ]
        );

        Campaign::updateOrCreate(
            ['title' => 'Community Health Education Drive'],
            [
                'user_id'            => $user->id,
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
            ]
        );

        Campaign::updateOrCreate(
            ['title' => 'School Supplies for Orphans'],
            [
                'user_id'            => $user->id,
                'description'        => 'Support orphaned students by providing backpacks, books, and stationery for the new school year.',
                'type'               => 'educational',
                'participation_type' => 'donation_and_volunteer',
                'amount_needed'      => 8000,
                'amount_collected'   => 0,
                'volunteers_needed'  => 10,
                'volunteers_joined'  => 0,
                'status'             => 'open',
                'start_date'         => '2026-07-01',
                'end_date'           => '2026-09-01',
            ]
        );

        Campaign::updateOrCreate(
            ['title' => 'Food Baskets for Families'],
            [
                'user_id'            => $user->id,
                'description'        => 'Collect donations and volunteer time to distribute food baskets to struggling families.',
                'type'               => 'humanitarian',
                'participation_type' => 'donation_and_volunteer',
                'amount_needed'      => 12000,
                'amount_collected'   => 0,
                'volunteers_needed'  => 15,
                'volunteers_joined'  => 0,
                'status'             => 'open',
                'start_date'         => '2026-05-20',
                'end_date'           => '2026-08-20',
            ]
        );

        Campaign::updateOrCreate(
            ['title' => 'Winter Clothing Drive'],
            [
                'user_id'            => $user->id,
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
            ]
        );
    }
}