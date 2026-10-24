<?php

namespace App\Sms;

use App\Contracts\SmsOtpSender;
use App\Models\MessageTemplate;
use App\Support\SmsIrPattern;
use RuntimeException;

/**
 * ارسال OTP با verify SMS.ir — همان الگوی foroshgahsaz/mainshop.
 */
class SmsIrOtpSender implements SmsOtpSender
{
    public const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

    public function sendOtp(string $phone, string $code): void
    {
        $template = MessageTemplate::query()->where('event_key', 'otp')->where('is_active', true)->first();
        $templateId = $template ? SmsIrPattern::templateId($template) : null;

        if ($templateId === null) {
            $templateId = (int) config('sms.smsir.otp_template_id');
        }

        if ($templateId < 1) {
            throw new RuntimeException('شناسه الگوی OTP در تنظیمات یا قالب پیامک ثبت نشده است.');
        }

        $map = $template
            ? SmsIrPattern::parameterMap($template)
            : ['otp_code' => (string) (config('sms.smsir.otp_parameter_name') ?: 'CODE')];

        $parameter = $map['otp_code'] ?? 'CODE';

        $result = app(SmsIrVerifySender::class)->send($phone, $templateId, [
            ['name' => $parameter, 'value' => $code],
        ]);

        if ($result->successful) {
            return;
        }

        $message = $result->errorMessage ?? 'ارسال پیامک با خطا مواجه شد.';

        throw new RuntimeException(str_starts_with($message, 'sms.ir:') ? $message : 'sms.ir: '.$message);
    }
}
