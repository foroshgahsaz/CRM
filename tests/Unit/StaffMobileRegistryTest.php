<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\User;
use App\Support\StaffMobileRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StaffMobileRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_mobile_does_not_block_staff_user(): void
    {
        Customer::factory()->create(['mobile' => '09107337161', 'full_name' => 'مشتری تست']);

        StaffMobileRegistry::assertAvailableForNewUser('09107337161');

        $this->assertTrue(true);
    }

    public function test_trashed_staff_user_does_not_block_same_mobile(): void
    {
        $ghost = User::factory()->create(['mobile' => '09107337161', 'email' => 'ghost@example.com']);
        $ghost->delete();

        StaffMobileRegistry::assertAvailableForNewUser('09107337161');

        $this->assertNull(User::withTrashed()->find($ghost->id)?->mobile);
    }

    public function test_active_staff_user_blocks_duplicate(): void
    {
        User::factory()->create(['mobile' => '09107337161', 'email' => 'active@example.com']);

        $this->expectException(ValidationException::class);

        StaffMobileRegistry::assertAvailableForNewUser('09107337161');
    }
}