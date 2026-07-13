<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_beneficiary_user_cannot_create_patient_request(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'user_category' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/storepatient', [
            'full_name' => 'Test User',
            'governorate_id' => 1,
            'region_id' => 1,
            'national_id' => '1234567890',
            'description' => 'test description',
        ]);

        $response->assertStatus(403);
    }
}
