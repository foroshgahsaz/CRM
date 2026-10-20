<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Support\MobileNumber;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(
        private SettingsService $settings,
        private SmsService $sms,
    ) {}

    public function issue(string $mobile, OtpPurpose $purpose, ?string $trackingCode, ?string $ip): void
    {
        $mobile = MobileNumber::normalize($mobile);

        if ($mobile === null) {
            throw new WorkflowException('شماره موبایل معتبر نیست.');
        }

        $latest = $this->latestOpen($mobile, $purpose, $trackingCode);

        if ($latest?->resend_available_at?->isFuture()) {
            throw new WorkflowException('ارسال مجدد هنوز امکان‌پذیر نیست. لطفاً کمی بعد تلاش کنید.');
        }

        $length = max(4, min(8, $this->settings->getInt('otp_length', 6)));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl = $this->settings->getInt('otp_ttl_seconds', 120);

        $challenge = OtpChallenge::query()->create([
            'mobile' => $mobile,
            'purpose' => $purpose,
            'tracking_code' => $trackingCode,
            'code_hash' => $this->hash($purpose, $mobile, $code),
            'expires_at' => now()->addSeconds($ttl),
            'attempts' => 0,
            'max_attempts' => $this->settings->getInt('otp_max_attempts', 5),
            'last_sent_at' => now(),
            'resend_available_at' => now()->addSeconds($this->settings->getInt('otp_resend_seconds', 60)),
            'send_count' => 1,
            'ip_address' => $ip,
        ]);

        $this->sms->sendImmediately($mobile, 'otp', [
            'otp_code' => $code,
            'company_name' => (string) $this->settings->get('company_name'),
            'tracking_code' => $trackingCode ?? '',
        ], null, 'otp:'.$challenge->id);

        if (config('crm.otp_debug')) {
            Log::warning('crm.otp_debug', [
                'mobile' => $mobile,
                'purpose' => $purpose->value,
                'code' => $code,
            ]);
        }
    }

    public function issueForStaff(string $mobile, ?string $ip): void
    {
        $normalized = MobileNumber::normalize($mobile);

        if ($normalized === null) {
            throw new WorkflowException('شماره موبایل معتبر نیست.');
        }

        $user = User::query()
            ->where('mobile', $normalized)
            ->where('is_active', true)
            ->whereHas('roles')
            ->first();

        if ($user === null || ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            throw new WorkflowException('این شماره برای ورود کارکنان ثبت نشده یا غیرفعال است.');
        }

        $this->issue($normalized, OtpPurpose::StaffLogin, null, $ip);
    }

    public function verifyStaffLogin(string $mobile, string $code): User
    {
        $this->verify($mobile, OtpPurpose::StaffLogin, null, $code);

        $normalized = MobileNumber::normalize($mobile);

        $user = User::query()
            ->where('mobile', $normalized)
            ->where('is_active', true)
            ->whereHas('roles')
            ->first();

        if ($user === null || ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            throw new WorkflowException('ورود با این شماره مجاز نیست.');
        }

        return $user;
    }

    public function verify(string $mobile, OtpPurpose $purpose, ?string $trackingCode, string $code): void
    {
        $mobile = MobileNumber::normalize($mobile);
        $code = MobileNumber::toEnglishDigits(trim($code));

        if ($mobile === null) {
            throw new WorkflowException('رمز یک‌بارمصرف نامعتبر یا منقضی شده است.');
        }

        $challenge = $this->latestOpen($mobile, $purpose, $trackingCode);

        if ($challenge === null || $challenge->expires_at->isPast()) {
            throw new WorkflowException('رمز یک‌بارمصرف نامعتبر یا منقضی شده است.');
        }

        if ($challenge->attempts >= $challenge->max_attempts) {
            throw new WorkflowException('تعداد تلاش‌های مجاز به پایان رسیده است.');
        }

        $challenge->increment('attempts');
        $challenge->refresh();

        if (! hash_equals($challenge->code_hash, $this->hash($purpose, $mobile, $code))) {
            if ($challenge->attempts >= $challenge->max_attempts) {
                throw new WorkflowException('تعداد تلاش‌های مجاز به پایان رسیده است.');
            }

            throw new WorkflowException('رمز یک‌بارمصرف نامعتبر یا منقضی شده است.');
        }

        $challenge->forceFill([
            'verified_at' => now(),
            'consumed_at' => now(),
        ])->save();
    }

    private function latestOpen(string $mobile, OtpPurpose $purpose, ?string $trackingCode): ?OtpChallenge
    {
        return OtpChallenge::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->where('tracking_code', $trackingCode)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();
    }

    private function hash(OtpPurpose $purpose, string $mobile, string $code): string
    {
        return hash_hmac('sha256', $purpose->value.'|'.$mobile.'|'.$code, (string) config('app.key'));
    }
}
