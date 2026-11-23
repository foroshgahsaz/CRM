<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Models\ServiceRequest;
use App\Models\User;

class UnseenAssignedRequestCounter
{
    public function countFor(User $user): int
    {
        return ServiceRequest::query()
            ->where('current_owner_id', $user->id)
            ->open()
            ->whereHas('currentAssignment', function ($assignment) use ($user): void {
                $assignment
                    ->where('to_user_id', $user->id)
                    ->where('status', AssignmentStatus::Active)
                    ->whereNull('first_viewed_at');
            })
            ->count();
    }
}
