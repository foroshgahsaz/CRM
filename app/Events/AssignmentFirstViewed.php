<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class AssignmentFirstViewed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $serviceRequestId, public int $assignmentId, public int $userId) {}
}
