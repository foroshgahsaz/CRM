<?php

namespace App\Policies;

use App\Models\SmsLog;
use App\Models\User;

class SmsLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sms_settings.manage');
    }

    public function view(User $user, SmsLog $log): bool
    {
        return $user->can('sms_settings.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SmsLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any');
    }

    public function delete(User $user, SmsLog $log): bool
    {
        return $user->can('system.delete_any');
    }
}
