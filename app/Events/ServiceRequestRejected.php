<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ServiceRequestRejected implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $serviceRequestId, public int $assignmentId) {}
}
