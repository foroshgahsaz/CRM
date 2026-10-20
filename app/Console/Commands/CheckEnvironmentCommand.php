<?php

namespace App\Console\Commands;

use App\Support\MysqlConnectionConfig;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Throwable;

class CheckEnvironmentCommand extends Command
{
    protected $signature = 'crm:check-env';

    protected $description = 'بررسی اتصال پایگاه داده و تنظیمات ضروری استقرار';

    public function handle(): int
    {
        $connection = (string) config('database.default');
        $this->info('اتصال پیش‌فرض: '.$connection);

        if ($connection === 'sqlite') {
            $path = (string) config('database.connections.sqlite.database');
            $this->warn('SQLite فعال است. برای production معمولاً DB_CONNECTION=mysql لازم است.');

            if (! is_file($path)) {
                $this->error('فایل SQLite وجود ندارد: '.$path);

                return self::FAILURE;
            }

            $this->info('فایل SQLite موجود است.');

            return self::SUCCESS;
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            $this->warn('این دستور برای mysql/mariadb طراحی شده است.');

            return self::SUCCESS;
        }

        $host = (string) config('database.connections.'.$connection.'.host');
        $port = (string) config('database.connections.'.$connection.'.port');
        $database = (string) config('database.connections.'.$connection.'.database');

        $this->line('DB_HOST مؤثر: '.$host);
        $this->line('DB_PORT: '.$port);
        $this->line('DB_DATABASE: '.$database);

        if (MysqlConnectionConfig::isLocalPlaceholder($host)) {
            $this->warn('DB_HOST روی localhost است. در Kubernetes/MySQL جداگانه معمولاً باید hostname سرویس دیتابیس باشد.');
            $this->line('  • در .env مقدار DB_HOST را عوض کنید، یا');
            $this->line('  • MYSQL_HOST / CRM_DB_HOST را در Deployment تزریق کنید (نسخه 1.1.1 اگر DB_HOST=127.0.0.1 باشد از MYSQL_HOST استفاده می‌کند).');
        }

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->warn('config:cache فعال است. بعد از تغییر .env حتماً php artisan config:clear و سپس config:cache بزنید.');
        }

        try {
            DB::connection()->getPdo();
            $this->info('اتصال MySQL موفق بود.');
        } catch (PDOException $exception) {
            if (str_contains($exception->getMessage(), '2002')) {
                $this->error('Connection refused - سرویس MySQL روی '.$host.':'.$port.' در دسترس نیست.');
            } else {
                $this->error('اتصال MySQL ناموفق: '.$exception->getMessage());
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('خطا: '.$exception->getMessage());

            return self::FAILURE;
        }

        $partial = $this->partialMigrationMessage();
        if ($partial !== null) {
            $this->error($partial);
            $this->line('اگر هنوز دادهٔ واقعی در CRM ندارید:');
            $this->line('  php artisan migrate:fresh --force');
            $this->line('  php artisan crm:create-admin --name="..." --email="..."');
            $this->line('در غیر این صورت قبل از migrate با DBA جدول‌های ناقص را حذف یا پشتیبان بگیرید.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function partialMigrationMessage(): ?string
    {
        if (! Schema::hasTable('migrations')) {
            return null;
        }

        if (Schema::hasTable('users') && ! Schema::hasTable('cache')) {
            return 'migrate ناقص است: جدول users وجود دارد ولی cache (و احتمالاً بقیهٔ جداول) ساخته نشده — معمولاً بعد از خطای migrate قبلی.';
        }

        if (Schema::hasTable('users') && ! Schema::hasTable('permissions')) {
            return 'migrate ناقص است: users هست ولی جدول permissions (Spatie) نیست.';
        }

        /** @var Migrator $migrator */
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = app('migration.repository')->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        if ($pending !== [] && Schema::hasTable('users') && in_array('0001_01_01_000000_create_users_table', $pending, true)) {
            return 'migrate گیر کرده: جدول users از اجرای قبلی مانده ولی migration users هنوز pending است (خطای 1050 Table already exists).';
        }

        return null;
    }
}
