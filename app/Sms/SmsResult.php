<?php

namespace App\Sms;

class SmsResult
{
    public function __construct(
        public bool $successful,
        public ?string $providerReference = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public bool $retryable = false,
    ) {}

    public static function sent(?string $reference): self
    {
        return new self(true, $reference);
    }

    public static function failed(string $code, string $message, bool $retryable): self
    {
        return new self(false, null, $code, $message, $retryable);
    }
}
