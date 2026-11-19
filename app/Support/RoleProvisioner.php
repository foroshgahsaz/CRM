<?php

namespace App\Support;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleProvisioner
{
    public static function ensure(string $roleName): void
    {
        if (! array_key_exists($roleName, PermissionCatalog::roles())) {
            return;
        }

        if (Role::query()->where('name', $roleName)->where('guard_name', 'web')->exists()) {
            return;
        }

        $role = Role::query()->create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(PermissionCatalog::forRole($roleName));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}