<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\UserRelatedRequestsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelatedRequestsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_requests_matching_staff_mobile_as_customer(): void
    {
        $user = User::factory()->create(['mobile' => '09107337161', 'email' => 'rep@example.com']);
        $customer = Customer::factory()->create(['mobile' => '09107337161']);
        $request = ServiceRequest::factory()->create(['customer_id' => $customer->id]);

        $ids = app(UserRelatedRequestsService::class)->relatedIds($user);

        $this->assertContains($request->id, $ids);
    }
}