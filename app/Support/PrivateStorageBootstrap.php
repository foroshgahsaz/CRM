<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PrivateStorageBootstrap
{
    /**
     * پوشه‌هایی که باید روی دیسک persistent (storage/app) قابل نوشتن برای PHP-FPM باشند.
     *
     * @return list<string>
     */
    public static function managedPaths(): array
    {
        $private = (string) config('filesystems.disks.local.root');

        return array_values(array_filter([
            $private,
            $private !== '' ? $private.DIRECTORY_SEPARATOR.'attachments' : null,
            $private !== '' ? $private.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'attachments' : null,
            (string) config('crm.backup.root'),
            storage_path('framework/backup-work'),
            storage_path('app/public'),
        ]));
    }

    public static function ensureRootExists(): void
    {
        foreach (self::managedPaths() as $path) {
            if ($path === '') {
                continue;
            }

            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0775, true, true);
            }
        }
    }

    /**
     * قبل از آپلود یا بکاپ از پنل (کاربر www-data) فراخوانی شود.
     */
    public static function assertWebWritable(string $path, string $context): void
    {
        self::ensureRootExists();

        if (! is_dir($path)) {
            throw new RuntimeException($context.' — پوشه وجود ندارد: '.$path);
        }

        if (! is_writable($path)) {
            throw new RuntimeException(
                $context.' — پوشه برای کاربر وب قابل نوشتن نیست: '.$path."\n"
                .'اگر بکاپ را با root از ترمینال زده‌اید، یک‌بار اجرا کنید: chown -R www-data:www-data storage/app'
            );
        }
    }
}