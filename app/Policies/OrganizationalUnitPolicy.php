<?php

namespace App\Policies;

use App\Models\OrganizationalUnit;
use App\Models\User;

class OrganizationalUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('units.manage');
    }

    public function view(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('units.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('units.manage');
    }

    public function update(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('units.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any') || $user->can('units.manage');
    }

    public function delete(User $user, OrganizationalUnit $unit): bool
    {
        return $user->can('system.delete_any')
            || ($user->can('units.manage') && $unit->users()->doesntExist() && $unit->children()->doesntExist());
    }
}
