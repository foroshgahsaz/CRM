<?php

namespace App\Support;

/**
 * مسیر فیزیکی مدارک روی دیسک پایدار لیارا (`crm -> /app/storage/app`).
 *
 * Laravel به‌صورت پیش‌فرض داخل `storage/app/private` می‌نویسد. اگر دیپلوی
 * پوشهٔ خالی تصویر را جایگزین آن کند، فایل‌ها از بین می‌روند. ریشهٔ دیسک
 * `local` باید خود `storage/app` باشد تا mount لیارا فایل‌ها را نگه دارد.
 */
final class PersistentStoragePath
{
    /**
     * مسیر ریشهٔ دیسک مدارک (بدون اسلش انتهایی).
     *
     * @param  array<string, mixed>  $env
     */
    public static function privateRootFrom(array $env, ?string $storageAppPath = null): string
    {
        $explicit = self::firstNonEmpty($env, [
            'CRM_STORAGE_PRIVATE_ROOT',
            'LIARA_STORAGE_PATH',
        ]);

        if ($explicit !== null) {
            return rtrim($explicit, DIRECTORY_SEPARATOR);
        }

        $storageApp = $storageAppPath ?? self::defaultStorageAppPath();

        return rtrim($storageApp, DIRECTORY_SEPARATOR);
    }

    public static function privateRoot(): string
    {
        return self::privateRootFrom([
            'CRM_STORAGE_PRIVATE_ROOT' => (string) env('CRM_STORAGE_PRIVATE_ROOT', ''),
            'LIARA_STORAGE_PATH' => (string) env('LIARA_STORAGE_PATH', ''),
        ]);
    }

    public static function backupRoot(): string
    {
        return self::backupRootFrom([
            'CRM_BACKUP_ROOT' => (string) env('CRM_BACKUP_ROOT', ''),
            'CRM_STORAGE_PRIVATE_ROOT' => (string) env('CRM_STORAGE_PRIVATE_ROOT', ''),
            'LIARA_STORAGE_PATH' => (string) env('LIARA_STORAGE_PATH', ''),
        ]);
    }

    /**
     * مسیر ریشهٔ بکاپ روی همان دیسک پایدار.
     *
     * @param  array<string, mixed>  $env
     */
    public static function backupRootFrom(array $env, ?string $storageAppPath = null): string
    {
        $explicit = self::firstNonEmpty($env, ['CRM_BACKUP_ROOT']);

        if ($explicit !== null) {
            return rtrim($explicit, DIRECTORY_SEPARATOR);
        }

        return self::privateRootFrom($env, $storageAppPath).DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'crm';
    }

    public static function defaultStorageAppPath(): string
    {
        return storage_path('app');
    }

    /**
     * اگر فایل هنوز زیر پوشهٔ قدیمی `private/` باشد، همان را برمی‌گرداند.
     */
    public static function existingFilePath(string $diskRoot, string $relativePath): ?string
    {
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '') {
            return null;
        }

        $candidates = [
            $diskRoot.DIRECTORY_SEPARATOR.$relativePath,
            $diskRoot.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.$relativePath,
        ];

        foreach ($candidates as $absolute) {
            if (is_file($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $env
     * @param  list<string>  $keys
     */
    private static function firstNonEmpty(array $env, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $env[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
