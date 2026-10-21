<?php

namespace App\Sms;

use App\Support\MobileNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SmsIrGateway implements SmsGateway
{
    private const BULK_URL = 'https://api.sms.ir/v1/send/bulk';

    private const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

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
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->post(self::BULK_URL, [
                    'lineNumber' => (int) $lineNumber,
                    'messageText' => $message,
                    'mobiles' => [$mobile],
                ]);
        } catch (ConnectionException) {
            return SmsResult::failed('connection', 'ارتباط با SMS.ir برقرار نشد.', true);
        }

        return $this->interpretResponse($response);
    }

    /**
     * @param  list<array{name: string, value: string}>  $parameters
     */
    public function sendVerify(string $mobile, int $templateId, array $parameters): SmsResult
    {
        $apiKey = config('sms.smsir.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return SmsResult::failed('not_configured', 'تنظیمات SMS.ir ناقص است.', false);
        }

        if ($templateId <= 0) {
            return SmsResult::failed('not_configured', 'شناسه قالب OTP در SMS.ir تنظیم نشده است.', false);
        }

        $apiMobile = MobileNumber::forSmsIrApi($mobile);

        try {
            $response = Http::timeout((int) config('sms.http.timeout', 10))
                ->acceptJson()
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->post(self::VERIFY_URL, [
                    'mobile' => $apiMobile,
                    'templateId' => $templateId,
                    'parameters' => array_values(array_map(
                        static fn (array $parameter): array => [
                            'name' => (string) ($parameter['name'] ?? ''),
                            'value' => (string) ($parameter['value'] ?? ''),
                        ],
                        $parameters,
                    )),
                ]);
        } catch (ConnectionException) {
            return SmsResult::failed('connection', 'ارتباط با SMS.ir برقرار نشد.', true);
        }

        return $this->interpretResponse($response, true);
    }

    public function otpTemplateId(): ?int
    {
        $templateId = config('sms.smsir.otp_template_id');

        if ($templateId === null || $templateId === '') {
            return null;
        }

        $parsed = (int) $templateId;

        return $parsed > 0 ? $parsed : null;
    }

    public function name(): string
    {
        return 'sms_ir';
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     */
    private function interpretResponse($response, bool $verify = false): SmsResult
    {
        if ($response->successful() && (int) $response->json('status') === 1) {
            $data = $response->json('data');
            $reference = null;

            if (is_array($data)) {
                if ($verify && isset($data['messageId']) && is_scalar($data['messageId'])) {
                    $reference = (string) $data['messageId'];
                } else {
                    $packId = $data['packId'] ?? null;
                    if (is_scalar($packId) && (string) $packId !== '') {
                        $reference = (string) $packId;
                    } elseif (isset($data['messageIds'][0]) && is_scalar($data['messageIds'][0])) {
                        $reference = (string) $data['messageIds'][0];
                    }
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
}
