<?php

namespace App\Sms;

use App\Contracts\SmsOtpSender;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * ارسال OTP با verify SMS.ir — همان الگوی foroshgahsaz/mainshop.
 */
class SmsIrOtpSender implements SmsOtpSender
{
    public const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

    public function sendOtp(string $phone, string $code): void
    {
        $apiKey = (string) config('sms.smsir.api_key');
        $templateId = (int) config('sms.smsir.otp_template_id');
        $parameter = (string) (config('sms.smsir.otp_parameter_name') ?: 'CODE');

        if ($apiKey === '') {
            throw new RuntimeException('کلید API سامانه sms.ir تنظیم نشده است.');
        }

        if ($templateId < 1) {
            throw new RuntimeException('شناسه قالب Verify در پنل sms.ir تنظیم نشده است.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders($this->headers($apiKey))
            ->post(self::VERIFY_URL, [
                'mobile' => $this->normalizeMobile($phone),
                'templateId' => $templateId,
                'parameters' => [
                    [
                        'name' => $parameter,
                        'value' => $code,
                    ],
                ],
            ]);

        $this->throwIfFailed($response);
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

    /** @return array<string, string> */
    protected function headers(string $apiKey): array
    {
        return [
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function throwIfFailed(Response $response): void
    {
        if ($response->status() === 401) {
            throw new RuntimeException('کلید API سامانه sms.ir نامعتبر است.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('تعداد درخواست پیامک بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');
        }

        $status = (int) $response->json('status');

        if ($response->successful() && $status === 1) {
            return;
        }

        $message = (string) ($response->json('message') ?: 'ارسال پیامک با خطا مواجه شد.');

        throw new RuntimeException('sms.ir: '.$message);
    }
}
