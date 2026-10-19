<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SystemSettingSeeder::class,
            RequestTypeSeeder::class,
            MessageTemplateSeeder::class,
            SlaPolicySeeder::class,
        ]);
    }
}
