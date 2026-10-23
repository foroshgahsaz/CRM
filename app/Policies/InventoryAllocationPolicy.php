<?php

namespace App\Policies;

use App\Models\InventoryAllocation;
use App\Models\User;

class InventoryAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view') || $user->can('inventory.manage');
    }

    public function view(User $user, InventoryAllocation $allocation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, InventoryAllocation $allocation): bool
    {
        return false;
    }

    public function delete(User $user, InventoryAllocation $allocation): bool
    {
        return false;
    }

    public function cancel(User $user, InventoryAllocation $allocation): bool
    {
        return $user->can('inventory.manage') && $allocation->isActive();
    }
}
