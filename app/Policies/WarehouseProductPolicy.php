<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Auth\Access\Response;

class WarehouseProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view') || $user->can('inventory.manage');
    }

    public function view(User $user, WarehouseProduct $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function update(User $user, WarehouseProduct $product): bool
    {
        return $user->can('inventory.manage');
    }

    public function delete(User $user, WarehouseProduct $product): bool
    {
        return $user->can('inventory.manage') && $product->allocations()->doesntExist();
    }

    public function allocate(User $user, WarehouseProduct $product): Response
    {
        if (! $user->can('inventory.allocate')) {
            return Response::deny('دسترسی لازم برای ارجاع کالا وجود ندارد.');
        }

        if (! $product->isInStock()) {
            return Response::deny('موجودی این کالا تمام شده است.');
        }

        return Response::allow();
    }
}
