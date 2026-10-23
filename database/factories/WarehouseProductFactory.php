<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseProduct>
 */
class WarehouseProductFactory extends Factory
{
    protected $model = WarehouseProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'name' => 'محصول '.fake()->unique()->numerify('###'),
            'code' => 'P-'.fake()->unique()->numerify('#####'),
            'quantity' => 10,
            'description' => null,
        ];
    }
}
