<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::permissions() as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        foreach (PermissionCatalog::roles() as $name => $label) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
            $role->syncPermissions(PermissionCatalog::forRole($name));
        }
    }
}
