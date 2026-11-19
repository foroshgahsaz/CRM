<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

class RoleLabelResolver
{
    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'نماینده' => 'representative',
        'کارشناس' => 'expert',
        'ناظر' => 'auditor',
        'مدیر ارشد' => 'super_admin',
        'مدیر ارشد سامانه' => 'super_admin',
        'مدیر عملیاتی' => 'system_administrator',
        'مدیر انبار' => 'warehouse_manager',
    ];

    public static function resolve(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $label = self::normalizeLabel($label);

        if ($label === '') {
            return null;
        }

        if (isset(self::ALIASES[$label])) {
            return self::ALIASES[$label];
        }

        $normalized = mb_strtolower($label);

        foreach (self::ALIASES as $alias => $name) {
            if (mb_strtolower($alias) === $normalized) {
                return $name;
            }
        }

        foreach (PermissionCatalog::roles() as $name => $persian) {
            if ($name === $label || $persian === $label || mb_strtolower($persian) === $normalized) {
                return $name;
            }
        }

        if (Role::query()->where('name', $label)->exists()) {
            return $label;
        }

        foreach (self::ALIASES as $alias => $name) {
            if (str_contains($label, $alias) || str_contains($normalized, mb_strtolower($alias))) {
                return $name;
            }
        }

        foreach (PermissionCatalog::roles() as $name => $persian) {
            if (str_contains($label, $persian) || str_contains($normalized, mb_strtolower($persian))) {
                return $name;
            }
        }

        return null;
    }

    public static function normalizeLabel(string $label): string
    {
        $label = trim($label);
        $label = MobileNumber::toEnglishDigits($label);
        $label = str_replace(["\u{FEFF}", "\u{200C}", "\u{200F}", "\u{202A}", "\u{202C}"], ' ', $label);
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

        return trim($label);
    }
}