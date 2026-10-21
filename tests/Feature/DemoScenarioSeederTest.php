<?php

namespace Tests\Feature;

use App\Models\RequestAssignment;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoScenarioSeeder;
use Tests\CrmTestCase;

class DemoScenarioSeederTest extends CrmTestCase
{
    public function test_demo_seeder_creates_ten_requests_with_staff_and_messages(): void
    {
        $this->seed(DemoScenarioSeeder::class);

        $this->assertSame(10, ServiceRequest::query()->where('submission_key', 'like', 'demo-seed-%')->count());
        $this->assertGreaterThanOrEqual(10, RequestAssignment::query()->count());
        $this->assertGreaterThanOrEqual(10, RequestMessage::query()->count());
        $this->assertSame(8, User::query()->where('email', 'like', '%@demo.local')->count());
    }
}
