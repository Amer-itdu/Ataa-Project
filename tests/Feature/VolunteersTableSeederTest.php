<?php

namespace Tests\Feature;

use App\Models\Volunteer;
use Database\Seeders\VolunteersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteersTableSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_volunteer_seeder_uses_supported_status_values(): void
    {
        $this->artisan('db:seed', ['--class' => VolunteersTableSeeder::class]);

        $statuses = Volunteer::query()
            ->whereIn('user_id', [1, 2])
            ->pluck('status')
            ->all();

        $allowedStatuses = ['pending', 'approved', 'rejected', 'suspended'];

        foreach ($statuses as $status) {
            $this->assertContains($status, $allowedStatuses);
        }
    }
}
