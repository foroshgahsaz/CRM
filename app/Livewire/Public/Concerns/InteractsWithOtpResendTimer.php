<?php

namespace App\Livewire\Public\Concerns;

use App\Enums\OtpPurpose;
use App\Services\OtpService;
use App\Support\MobileNumber;

trait InteractsWithOtpResendTimer
{
    abstract protected function otpPurpose(): OtpPurpose;

    abstract protected function otpTrackingCode(): ?string;

    abstract protected function otpMobile(): string;

    public function otpResendRemainingSeconds(): int
    {
        $mobile = MobileNumber::normalize($this->otpMobile());

        if ($mobile === null) {
            return 0;
        }

        return app(OtpService::class)->resendCooldownRemainingSeconds(
            $mobile,
            $this->otpPurpose(),
            $this->otpTrackingCode(),
        );
    }

    public function hasPendingOtpForContext(): bool
    {
        $mobile = MobileNumber::normalize($this->otpMobile());

        if ($mobile === null) {
            return false;
        }

        return app(OtpService::class)->hasPendingOtp(
            $mobile,
            $this->otpPurpose(),
            $this->otpTrackingCode(),
        );
    }
}
