<?php

namespace App\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSmsGateway implements SmsGateway
{
    /** @var list<array{mobile: string, message: string}> */
    public static array $captured = [];

    public function send(string $mobile, string $message): SmsResult
    {
        if (app()->environment('production')) {
            return SmsResult::failed('not_configured', 'درگاه پیامک پیکربندی نشده است.', false);
        }

        self::$captured[] = [
            'mobile' => $mobile,
            'message' => $message,
        ];

        if (config('sms.log_codes') && app()->environment('local')) {
            Log::warning('sms.local_debug', [
                'recipient' => $mobile,
                'message' => $message,
            ]);
        }

        return SmsResult::sent('log-'.Str::uuid());
    }

    public function name(): string
    {
        return 'log';
    }
}
