<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any') || $user->can('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return $role->name !== 'super_admin'
            && ($user->can('system.delete_any') || ($user->can('roles.manage') && $role->users()->doesntExist()));
    }
}
