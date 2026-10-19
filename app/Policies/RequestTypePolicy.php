<?php

namespace App\Policies;

use App\Models\RequestType;
use App\Models\User;

class RequestTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('request_types.manage') || $user->can('form_fields.manage');
    }

    public function view(User $user, RequestType $type): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('request_types.manage');
    }

    public function update(User $user, RequestType $type): bool
    {
        return $user->can('request_types.manage') || $user->can('form_fields.manage');
    }

    public function delete(User $user, RequestType $type): bool
    {
        return $user->can('request_types.manage') && $type->serviceRequests()->doesntExist();
    }
}
