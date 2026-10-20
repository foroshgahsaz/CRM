<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BootstrapEnvCommand extends Command
{
    protected $signature = 'crm:bootstrap-env {--force : بازنویسی .env موجود}';

    protected $description = 'ساخت .env از .env.example (فقط روی سرور؛ این فایل در Git نیست)';

    public function handle(): int
    {
        $target = base_path('.env');
        $example = base_path('.env.example');

        if (! is_file($example)) {
            $this->error('فایل .env.example یافت نشد.');

            return self::FAILURE;
        }

        if (is_file($target) && ! $this->option('force')) {
            $this->warn('.env از قبل وجود دارد. برای بازنویسی از --force استفاده کنید.');

            return self::SUCCESS;
        }

        if (! copy($example, $target)) {
            $this->error('کپی .env.example به .env انجام نشد. دسترسی نوشتن در /var/www را بررسی کنید.');

            return self::FAILURE;
        }

        $this->info('.env ساخته شد.');
        $this->line('۱) DB_*، APP_URL، SMS و بقیه را در .env ویرایش کنید.');
        $this->line('۲) php artisan key:generate');
        $this->line('۳) php artisan config:clear && php artisan crm:check-env');

        return self::SUCCESS;
    }
}
