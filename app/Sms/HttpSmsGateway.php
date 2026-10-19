<?php

namespace App\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class HttpSmsGateway implements SmsGateway
{
    public function send(string $mobile, string $message): SmsResult
    {
        $url = config('sms.http.url');
        $token = config('sms.http.token');

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            return SmsResult::failed('not_configured', 'تنظیمات درگاه پیامک ناقص است.', false);
        }

        try {
            $response = Http::timeout((int) config('sms.http.timeout', 10))
                ->withToken($token)
                ->acceptJson()
                ->post($url, [
                    'to' => $mobile,
                    'message' => $message,
                    'sender' => config('sms.http.sender'),
                ]);
        } catch (ConnectionException) {
            return SmsResult::failed('connection', 'ارتباط با درگاه پیامک برقرار نشد.', true);
        }

        if ($response->successful()) {
            $reference = $response->json('reference') ?? $response->json('message_id');

            return SmsResult::sent(is_scalar($reference) ? (string) $reference : null);
        }

        return SmsResult::failed(
            (string) $response->status(),
            'ارسال پیامک توسط درگاه رد شد.',
            $response->serverError() || $response->status() === 429,
        );
    }

    public function name(): string
    {
        return 'http';
    }
}
