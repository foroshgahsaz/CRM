<?php

namespace App\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * ارسال با API verify SMS.ir (الگوی از پیش تعریف‌شده در پنل sms.ir).
 */
class SmsIrVerifySender
{
    public const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

    /**
     * @param  list<array{name: string, value: string}>  $parameters
     */
    public function send(string $mobile, int $templateId, array $parameters): SmsResult
    {
        $apiKey = config('sms.smsir.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return SmsResult::failed('not_configured', 'کلید API سامانه sms.ir تنظیم نشده است.', false);
        }

        if ($templateId < 1) {
            return SmsResult::failed('not_configured', 'شناسه الگوی SMS.ir تنظیم نشده است.', false);
        }

        if ($parameters === []) {
            return SmsResult::failed('invalid_parameters', 'پارامترهای الگو خالی است.', false);
        }

        try {
            $response = Http::timeout((int) config('sms.http.timeout', 15))
                ->acceptJson()
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post(self::VERIFY_URL, [
                    'mobile' => $this->normalizeMobile($mobile),
                    'templateId' => $templateId,
                    'parameters' => $parameters,
                ]);
        } catch (ConnectionException) {
            return SmsResult::failed('connection', 'ارتباط با SMS.ir برقرار نشد.', true);
        }

        if ($response->status() === 401) {
            return SmsResult::failed('401', 'کلید API سامانه sms.ir نامعتبر است.', false);
        }

        if ($response->status() === 429) {
            return SmsResult::failed('429', 'تعداد درخواست پیامک بیش از حد مجاز است.', true);
        }

        if ($response->successful() && (int) $response->json('status') === 1) {
            $messageId = $response->json('data.messageId');

            return SmsResult::sent(is_scalar($messageId) ? (string) $messageId : null);
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

    public function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
