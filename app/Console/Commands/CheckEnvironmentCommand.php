<?php

namespace App\Console\Commands;

use App\Support\MysqlConnectionConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        return self::SUCCESS;
    }
}
