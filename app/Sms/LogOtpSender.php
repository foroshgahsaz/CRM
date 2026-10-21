<?php

namespace App\Sms;

use App\Contracts\SmsOtpSender;
use Illuminate\Support\Facades\Log;
class LogOtpSender implements SmsOtpSender
{
    public function sendOtp(string $phone, string $code): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('درگاه OTP پیکربندی نشده است.');
        }

        LogSmsGateway::$captured[] = [
            'mobile' => $phone,
            'message' => "رمز تأیید شما {$code} است.",
        ];

        if (config('sms.log_codes') && app()->environment('local')) {
            Log::warning('sms.local_debug', [
                'recipient' => $phone,
                'message' => "OTP {$code}",
            ]);
        }
    }

    public function name(): string
    {
        return 'log';
    }
}
