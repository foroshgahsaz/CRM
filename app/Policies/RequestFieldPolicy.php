<?php

namespace App\Policies;

use App\Models\RequestField;
use App\Models\User;

class RequestFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('form_fields.manage') || $user->can('request_types.manage');
    }

    public function view(User $user, RequestField $field): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('form_fields.manage');
    }

    public function update(User $user, RequestField $field): bool
    {
        return $user->can('form_fields.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any') || $user->can('form_fields.manage');
    }

    public function delete(User $user, RequestField $field): bool
    {
        return $user->can('system.delete_any')
            || ($user->can('form_fields.manage') && $field->values()->doesntExist());
    }
}
