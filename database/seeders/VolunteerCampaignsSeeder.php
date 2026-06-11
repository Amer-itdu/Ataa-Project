<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Campaign;
use App\Models\VolunteerCampaign;

class VolunteerCampaignsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1) جلب المستخدمين (أحمد – ميلاد – سدرة)
        |--------------------------------------------------------------------------
        */

        $ahmad = User::where('email', 'ahmad.zen@email.com')->first();
        $milad = User::where('email', 'milad.hamad@example.com')->first();
        $sedra = User::where('email', 'sedra.jlilaty@example.com')->first();

        /*
        |--------------------------------------------------------------------------
        | 2) إنشاء متطوعين مرتبطين بالمستخدمين
        |--------------------------------------------------------------------------
        */

        if ($ahmad) {
            $volAhmad = Volunteer::updateOrCreate(
                ['user_id' => $ahmad->id],
                [
                    'skills' => 'Management, Logistics',
                    'description' => 'Experienced in organizing events.',
                    'status' => 'active',
                ]
            );
        }

        if ($milad) {
            $volMilad = Volunteer::updateOrCreate(
                ['user_id' => $milad->id],
                [
                    'skills' => 'First Aid, Field Support',
                    'description' => 'Ready for field volunteering.',
                    'status' => 'active',
                ]
            );
        }

        if ($sedra) {
            $volSedra = Volunteer::updateOrCreate(
                ['user_id' => $sedra->id],
                [
                    'skills' => 'Teaching, Social Support',
                    'description' => 'Loves helping children and families.',
                    'status' => 'active',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3) جلب الحملات التي أنشأتها في CampaignsTableSeeder
        |--------------------------------------------------------------------------
        */

        $camp1 = Campaign::where('title', 'Clean Water for Every Village')->first();
        $camp2 = Campaign::where('title', 'Community Health Education Drive')->first();
        $camp3 = Campaign::where('title', 'School Supplies for Orphans')->first();
        $camp4 = Campaign::where('title', 'Food Baskets for Families')->first();
        $camp5 = Campaign::where('title', 'Winter Clothing Drive')->first();

        /*
        |--------------------------------------------------------------------------
        | 4) ربط المتطوعين بالحملات (ثابت بدون حلقات)
        |--------------------------------------------------------------------------
        */

        // أحمد يتطوع لحملة 1 + 2
        if (isset($volAhmad) && $camp1) {
            VolunteerCampaign::updateOrCreate(
                [
                    'volunteer_id' => $volAhmad->id,
                    'campaign_id' => $camp1->id,
                ],
                [
                    'status' => 'approved',
                    'assigned_date' => now(),
                ]
            );
        }

        if (isset($volAhmad) && $camp2) {
            VolunteerCampaign::updateOrCreate(
                [
                    'volunteer_id' => $volAhmad->id,
                    'campaign_id' => $camp2->id,
                ],
                [
                    'status' => 'pending',
                    'assigned_date' => now(),
                ]
            );
        }

        // ميلاد يتطوع لحملة 3
        if (isset($volMilad) && $camp3) {
            VolunteerCampaign::updateOrCreate(
                [
                    'volunteer_id' => $volMilad->id,
                    'campaign_id' => $camp3->id,
                ],
                [
                    'status' => 'approved',
                    'assigned_date' => now(),
                ]
            );
        }

        // سدرة تتطوع لحملة 4 + 5
        if (isset($volSedra) && $camp4) {
            VolunteerCampaign::updateOrCreate(
                [
                    'volunteer_id' => $volSedra->id,
                    'campaign_id' => $camp4->id,
                ],
                [
                    'status' => 'pending',
                    'assigned_date' => now(),
                ]
            );
        }

        if (isset($volSedra) && $camp5) {
            VolunteerCampaign::updateOrCreate(
                [
                    'volunteer_id' => $volSedra->id,
                    'campaign_id' => $camp5->id,
                ],
                [
                    'status' => 'approved',
                    'assigned_date' => now(),
                ]
            );
        }
    }
}
