<?php

namespace App\Support;

class MysqlConnectionConfig
{
    /**
     * @param  array<string, mixed>  $env
     */
    public static function hostFrom(array $env): string
    {
        $configured = self::stringValue($env['DB_HOST'] ?? null);
        $injected = self::stringValue($env['MYSQL_HOST'] ?? null)
            ?? self::stringValue($env['CRM_DB_HOST'] ?? null);

        if ($injected !== null && self::isLocalPlaceholder($configured)) {
            return $injected;
        }

        return $configured ?? '127.0.0.1';
    }

    /**
     * @param  array<string, mixed>  $env
     */
    public static function portFrom(array $env): string
    {
        return self::stringValue($env['DB_PORT'] ?? null)
            ?? self::stringValue($env['MYSQL_PORT'] ?? null)
            ?? '3306';
    }

    /**
     * @param  array<string, mixed>  $env
     */
    public static function databaseFrom(array $env): string
    {
        return self::stringValue($env['DB_DATABASE'] ?? null)
            ?? self::stringValue($env['MYSQL_DATABASE'] ?? null)
            ?? 'laravel';
    }

    /**
     * @param  array<string, mixed>  $env
     */
    public static function usernameFrom(array $env): string
    {
        return self::stringValue($env['DB_USERNAME'] ?? null)
            ?? self::stringValue($env['MYSQL_USER'] ?? null)
            ?? 'root';
    }

    /**
     * @param  array<string, mixed>  $env
     */
    public static function passwordFrom(array $env): string
    {
        return self::stringValue($env['DB_PASSWORD'] ?? null)
            ?? self::stringValue($env['MYSQL_PASSWORD'] ?? null)
            ?? '';
    }

    public static function host(): string
    {
        return self::hostFrom($_ENV + $_SERVER);
    }

    public static function port(): string
    {
        return self::portFrom($_ENV + $_SERVER);
    }

    public static function database(): string
    {
        return self::databaseFrom($_ENV + $_SERVER);
    }

    public static function username(): string
    {
        return self::usernameFrom($_ENV + $_SERVER);
    }

    public static function password(): string
    {
        return self::passwordFrom($_ENV + $_SERVER);
    }

    public static function isLocalPlaceholder(?string $host): bool
    {
        return $host === null || $host === '' || $host === '127.0.0.1' || $host === 'localhost';
    }

    private static function stringValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
