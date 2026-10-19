<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    public function run(): void
    {
        SlaPolicy::query()->firstOrCreate(
            ['name' => 'مهلت پیش‌فرض رسیدگی'],
            [
                'response_minutes' => 240,
                'resolution_minutes' => 1620,
                'use_business_hours' => true,
                'is_active' => true,
            ],
        );
    }
}
