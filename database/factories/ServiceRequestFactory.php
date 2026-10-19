<?php

namespace Database\Factories;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Models\Customer;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_code' => 'RQ-'.strtoupper(Str::random(10)),
            'customer_id' => Customer::factory(),
            'request_type_id' => RequestType::factory(),
            'purchase_date' => now()->subDays(2)->toDateString(),
            'invoice_number' => fake()->numerify('INV-####'),
            'product_name' => fake()->words(3, true),
            'purchased_quantity' => 2,
            'affected_quantity' => 1,
            'description' => fake()->paragraph(),
            'status' => RequestStatus::PendingAssignment,
            'priority' => RequestPriority::Normal,
            'submitted_at' => now(),
            'submission_key' => (string) Str::uuid(),
        ];
    }
}
