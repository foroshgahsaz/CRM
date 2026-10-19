<?php

namespace Database\Seeders;

use App\Models\RequestType;
use Illuminate\Database\Seeder;

class RequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'شکستگی کالا', 'slug' => 'breakage', 'description' => 'کالای خریداری‌شده شکسته یا آسیب‌دیده است.', 'sort_order' => 1],
            ['name' => 'مغایرت کالا یا اقلام', 'slug' => 'mismatch', 'description' => 'کالا یا اقلام دریافتی با سفارش مغایرت دارد.', 'sort_order' => 2],
            ['name' => 'کسری اقلام', 'slug' => 'shortage', 'description' => 'بخشی از اقلام فاکتور دریافت نشده است.', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            RequestType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [...$type, 'is_active' => true],
            );
        }
    }
}
