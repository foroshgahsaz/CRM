<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'crm:install
        {--fresh : حذف همه جداول و migrate از نو (فقط نصب تازه)}
        {--skip-admin : فقط migrate و seed، بدون ساخت مدیر}';

    protected $description = 'migrate + seed دادهٔ اولیه (نوع درخواست، قالب پیامک، …)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        if (! Schema::hasTable('request_types')) {
            $this->error('جدول request_types ساخته نشد. migrate را بررسی کنید.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--force' => true]);

        $this->info('Seed اولیه انجام شد (نوع درخواست، تنظیمات، قالب پیامک، SLA).');

        if (! $this->option('skip-admin')) {
            if (config('crm.seed_default_admin')) {
                $email = (string) config('crm.default_admin.email');
                $this->info("مدیر پیش‌فرض از seed: نام کاربری admin یا ایمیل {$email} — رمز در config/crm (CRM_DEFAULT_ADMIN_PASSWORD).");

                return self::SUCCESS;
            }

            return $this->call('crm:create-admin');
        }

        return self::SUCCESS;
    }
}
