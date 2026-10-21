<?php

namespace App\Console\Commands;

use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Console\Command;

class SeedDemoCommand extends Command
{
    protected $signature = 'crm:seed-demo';

    protected $description = 'دادهٔ نمایشی: ۱۰ درخواست، واحدها، کارشناسان، ارجاعات و پیام‌ها';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => DemoScenarioSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
