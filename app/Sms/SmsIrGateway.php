<?php

namespace App\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SmsIrGateway implements SmsGateway
{
    public const BULK_URL = 'https://api.sms.ir/v1/send/bulk';

    public function send(string $mobile, string $message): SmsResult
    {
        $apiKey = config('sms.smsir.api_key');
        $lineNumber = config('sms.smsir.line_number');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($lineNumber) || $lineNumber === '') {
            return SmsResult::failed('not_configured', 'تنظیمات SMS.ir ناقص است.', false);
        }

        if (! ctype_digit($lineNumber)) {
            return SmsResult::failed('not_configured', 'شماره خط SMS.ir نامعتبر است.', false);
        }

        try {
            $response = Http::timeout((int) config('sms.http.timeout', 10))
                ->acceptJson()
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post(self::BULK_URL, [
                    'lineNumber' => (int) $lineNumber,
                    'messageText' => $message,
                    'mobiles' => [$mobile],
                ]);
        } catch (ConnectionException) {
            return SmsResult::failed('connection', 'ارتباط با SMS.ir برقرار نشد.', true);
        }

        if ($response->successful() && (int) $response->json('status') === 1) {
            $data = $response->json('data');
            $reference = null;

            if (is_array($data)) {
                $packId = $data['packId'] ?? null;
                if (is_scalar($packId) && (string) $packId !== '') {
                    $reference = (string) $packId;
                } elseif (isset($data['messageIds'][0]) && is_scalar($data['messageIds'][0])) {
                    $reference = (string) $data['messageIds'][0];
                }
            }

            return SmsResult::sent($reference);
        }

        $providerMessage = $response->json('message');
        $errorMessage = is_string($providerMessage) && $providerMessage !== ''
            ? $providerMessage
            : 'ارسال پیامک توسط SMS.ir رد شد.';

        return SmsResult::failed(
            (string) ($response->json('status') ?? $response->status()),
            $errorMessage,
            $response->serverError() || $response->status() === 429,
        );
    }

    public function name(): string
    {
        return 'sms_ir';
    }
}
