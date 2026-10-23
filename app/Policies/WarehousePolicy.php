<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view') || $user->can('warehouses.manage');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.manage');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.manage');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.manage') && $warehouse->products()->doesntExist();
    }
}
