<?php

namespace App\Policies;

use App\Models\MessageTemplate;
use App\Models\User;

class MessageTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sms_settings.manage');
    }

    public function view(User $user, MessageTemplate $template): bool
    {
        return $user->can('sms_settings.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        return $user->can('sms_settings.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any');
    }

    public function delete(User $user, MessageTemplate $template): bool
    {
        return $user->can('system.delete_any');
    }
}
