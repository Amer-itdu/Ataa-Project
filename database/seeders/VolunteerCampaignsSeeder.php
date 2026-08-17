<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Database\Seeder;

class VolunteerCampaignsSeeder extends Seeder
{
    public function run(): void
    {
        $ahmad = User::where('email', 'ahmad.zen@email.com')->first();
        $milad = User::where('email', 'milad.hamad@example.com')->first();
        $sedra = User::where('email', 'sedra.jlilaty@example.com')->first();

        $camp1 = Campaign::where('title', 'Clean Water for Every Village')->first();
        $camp2 = Campaign::where('title', 'Community Health Education Drive')->first();
        $camp3 = Campaign::where('title', 'School Supplies for Orphans')->first();
        $camp4 = Campaign::where('title', 'Food Baskets for Families')->first();
        $camp5 = Campaign::where('title', 'Winter Clothing Drive')->first();

        // ahmad
        if ($ahmad) {
            $volAhmad = Volunteer::firstOrCreate(
                ['user_id' => $ahmad->id],
                [
                    'phone'              => '0999000002',
                    'gender'             => 'male',
                    'governorate_id'     => 1,
                    'skills'             => ['field_work'],
                    'status'             => 'approved',
                    'agreed_to_terms'    => true,
                    'agreed_to_terms_at' => now(),
                ]
            );

            if ($camp1 && !$camp1->volunteers()->where('volunteer_id', $volAhmad->id)->exists()) {
                $camp1->volunteers()->attach($volAhmad->id, [
                    'status'        => 'approved',
                    'assigned_date' => now(),
                ]);
            }

            if ($camp2 && !$camp2->volunteers()->where('volunteer_id', $volAhmad->id)->exists()) {
                $camp2->volunteers()->attach($volAhmad->id, [
                    'status'        => 'pending',
                    'assigned_date' => null,
                ]);
            }
        }

        // milad
        if ($milad) {
            $volMilad = Volunteer::firstOrCreate(
                ['user_id' => $milad->id],
                [
                    'phone'              => '0999000003',
                    'gender'             => 'male',
                    'governorate_id'     => 2,
                    'skills'             => ['teaching', 'event_management'],
                    'status'             => 'approved',
                    'agreed_to_terms'    => true,
                    'agreed_to_terms_at' => now(),
                ]
            );

            if ($camp3 && !$camp3->volunteers()->where('volunteer_id', $volMilad->id)->exists()) {
                $camp3->volunteers()->attach($volMilad->id, [
                    'status'        => 'approved',
                    'assigned_date' => now(),
                ]);
            }
        }

        // sedra
        if ($sedra) {
            $volSedra = Volunteer::firstOrCreate(
                ['user_id' => $sedra->id],
                [
                    'phone'              => '0999000004',
                    'gender'             => 'female',
                    'governorate_id'     => 3,
                    'skills'             => ['counseling_mental_health'],
                    'status'             => 'approved',
                    'agreed_to_terms'    => true,
                    'agreed_to_terms_at' => now(),
                ]
            );

            if ($camp4 && !$camp4->volunteers()->where('volunteer_id', $volSedra->id)->exists()) {
                $camp4->volunteers()->attach($volSedra->id, [
                    'status'        => 'pending',
                    'assigned_date' => null,
                ]);
            }

            if ($camp5 && !$camp5->volunteers()->where('volunteer_id', $volSedra->id)->exists()) {
                $camp5->volunteers()->attach($volSedra->id, [
                    'status'        => 'approved',
                    'assigned_date' => now(),
                ]);
            }
        }

        // 🔥 تحديث volunteers_joined من الفعلي (هذا هو الحل)
        collect([$camp1, $camp2, $camp3, $camp4, $camp5])->each(function ($campaign) {
            if ($campaign) {
                $approvedCount = $campaign->volunteers()
                    ->wherePivot('status', 'approved')
                    ->count();
                
                // 🔥 حدّث volunteers_joined بالعدد الفعلي
                $campaign->update(['volunteers_joined' => $approvedCount]);
            }
        });
    }
}