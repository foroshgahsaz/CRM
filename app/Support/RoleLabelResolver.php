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

        $label = trim($label);

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

        return null;
    }
}