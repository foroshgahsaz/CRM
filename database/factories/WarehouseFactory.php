<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'انبار '.fake()->unique()->numerify('###'),
            'code' => 'WH-'.fake()->unique()->numerify('####'),
            'location' => null,
            'description' => null,
            'is_active' => true,
        ];
    }
}
