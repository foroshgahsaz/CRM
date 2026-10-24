<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit_logs.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->can('audit_logs.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('system.delete_any');
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return $user->can('system.delete_any');
    }
}
