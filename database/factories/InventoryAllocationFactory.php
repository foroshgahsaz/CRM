<?php

namespace Database\Factories;

use App\Models\InventoryAllocation;
use App\Models\ServiceRequest;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAllocation>
 */
class InventoryAllocationFactory extends Factory
{
    protected $model = InventoryAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_product_id' => WarehouseProduct::factory(),
            'service_request_id' => ServiceRequest::factory(),
            'product_name_snapshot' => 'محصول',
            'product_code_snapshot' => 'P-1',
            'warehouse_name_snapshot' => 'انبار',
            'allocated_by' => null,
            'note' => null,
        ];
    }
}
