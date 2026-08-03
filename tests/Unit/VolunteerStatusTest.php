<?php

namespace Tests\Unit;

use App\Models\Volunteer;
use PHPUnit\Framework\TestCase;

class VolunteerStatusTest extends TestCase
{
    public function test_active_status_is_normalized_to_approved(): void
    {
        $this->assertSame('approved', Volunteer::normalizeStatus('active'));
    }

    public function test_pending_status_is_preserved(): void
    {
        $this->assertSame('pending', Volunteer::normalizeStatus('pending'));
    }
}
