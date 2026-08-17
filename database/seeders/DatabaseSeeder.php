<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\CampaignsTableSeeder;
use Database\Seeders\RequestsTableSeeder;
use Database\Seeders\VolunteersTableSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
  public function run(): void
{
    $this->call([
        GovernorateRegionSeeder::class,
        UsersTableSeeder::class,
        RequestsTableSeeder::class,
        CampaignsTableSeeder::class,
        VolunteersTableSeeder::class,
        VolunteerCampaignsSeeder::class,        // 🔥 قبل DonationsTableSeeder
        DonationsTableSeeder::class,            // 🔥 آخر واحد
    ]);
}
}