<?php

namespace App\Sms;

use App\Contracts\SmsOtpSender;

class OtpSmsSenderFactory
{
    public function make(): SmsOtpSender
    {
        return match (config('sms.gateway')) {
            'sms_ir' => app(SmsIrOtpSender::class),
            default => app(LogOtpSender::class),
        };
    }
}
