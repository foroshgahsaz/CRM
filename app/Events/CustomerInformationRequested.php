<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerInformationRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $serviceRequestId, public int $messageId) {}
}
