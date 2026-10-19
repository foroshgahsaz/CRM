<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 20;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function __construct(
        public string $mobile,
        public string $eventKey,
        public array $variables,
        public ?int $serviceRequestId,
        public string $idempotencyKey,
    ) {}

    public function handle(SmsService $sms): void
    {
        $sms->deliver(
            $this->mobile,
            $this->eventKey,
            $this->variables,
            $this->serviceRequestId,
            $this->idempotencyKey,
        );
    }

    public function failed(?Throwable $exception): void
    {
        // The SMS log already records the provider error. Nothing sensitive is added here.
    }
}
