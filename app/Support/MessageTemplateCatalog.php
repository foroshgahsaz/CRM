<?php

namespace App\Support;

class MessageTemplateCatalog
{
    /**
     * @return array<string, string>
     */
    public static function eventLabels(): array
    {
        return [
            'otp' => 'رمز یک‌بارمصرف (OTP)',
            'request_submitted' => 'ثبت درخواست و کد رهگیری',
            'customer_info_requested' => 'درخواست مدرک از مشتری',
            'request_closed' => 'نتیجه رسیدگی و بستن پرونده',
        ];
    }

    public static function eventLabel(string $eventKey): string
    {
        return self::eventLabels()[$eventKey] ?? $eventKey;
    }

    public static function usesSmsIrPatterns(): bool
    {
        return config('sms.gateway') === 'sms_ir';
    }
}
