<?php

namespace App\Livewire\Public;

use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Livewire\Public\Concerns\InteractsWithOtpResendTimer;
use App\Services\OtpService;
use App\Support\MobileNumber;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SubmissionOtpFlow extends Component
{
    use InteractsWithOtpResendTimer;

    public string $step = 'phone';

    public string $mobile = '';

    public string $otp = '';

    public function mount(): void
    {
        if (session('submission.verified') === true) {
            return;
        }

        $sessionMobile = session('submission.mobile');

        if (is_string($sessionMobile)) {
            $this->mobile = $sessionMobile;
        }

        if (session('submission.flow_step') === 'otp' && preg_match('/^09\d{9}$/', $this->mobile)) {
            $this->step = 'otp';
        }

        $this->advanceToOtpIfPending();
    }

    public function hydrate(): void
    {
        $this->advanceToOtpIfPending();
    }

    public function updatedMobile(): void
    {
        $normalized = MobileNumber::normalize($this->mobile);
        $this->mobile = $normalized ?? $this->mobile;
        $this->advanceToOtpIfPending();
    }

    public function sendOtp(OtpService $otp): void
    {
        $normalized = MobileNumber::normalize($this->mobile);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
            ]);
        }

        $this->mobile = $normalized;

        $this->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'mobile.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
        ]);

        $rateKey = 'otp-send:'.$this->mobile;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages([
                'mobile' => 'تلاش‌های زیاد. لطفاً بعداً دوباره امتحان کنید.',
            ]);
        }

        if ($otp->resendCooldownRemainingSeconds($this->mobile, OtpPurpose::Submit, null) > 0) {
            $this->markOtpStep();

            return;
        }

        try {
            $otp->issue($this->mobile, OtpPurpose::Submit, null, request()->ip());
        } catch (WorkflowException $exception) {
            if ($otp->hasPendingOtp($this->mobile, OtpPurpose::Submit, null)) {
                $this->markOtpStep();

                throw ValidationException::withMessages([
                    'otp' => $exception->getMessage(),
                ]);
            }

            throw ValidationException::withMessages([
                'mobile' => $exception->getMessage(),
            ]);
        }

        RateLimiter::hit($rateKey, 300);
        session()->put('submission.mobile', $this->mobile);
        session()->forget('submission.verified');
        $this->markOtpStep();
        session()->flash('otp_status', 'کد تأیید به شماره شما ارسال شد.');
    }

    public function verifyOtp(OtpService $otp): void
    {
        $normalized = MobileNumber::normalize($this->mobile);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'otp' => 'ابتدا شماره موبایل را وارد کنید.',
            ]);
        }

        $this->mobile = $normalized;

        $this->otp = MobileNumber::toEnglishDigits(trim($this->otp));

        $length = $otp->otpLength();

        $this->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'otp' => ['required', 'digits:'.$length],
        ], [
            'otp.digits' => "کد تأیید باید {$length} رقم باشد.",
        ]);

        $rateKey = 'otp-verify:'.$this->mobile;

        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'تلاش‌های زیاد. لطفاً کد جدید درخواست کنید.',
            ]);
        }

        try {
            $otp->verify($this->mobile, OtpPurpose::Submit, null, $this->otp);
        } catch (WorkflowException $exception) {
            RateLimiter::hit($rateKey, 120);
            throw ValidationException::withMessages([
                'otp' => $exception->getMessage(),
            ]);
        }

        RateLimiter::clear($rateKey);
        session()->put('submission_bootstrap', [
            'mobile' => $this->mobile,
            'key' => Str::random(40),
        ]);
        session()->regenerate();

        $this->redirectRoute('requests.access.bootstrap', navigate: false);
    }

    public function backToPhone(): void
    {
        $this->step = 'phone';
        $this->otp = '';
        session()->forget('submission.flow_step');
        $this->resetValidation();
    }

    protected function markOtpStep(): void
    {
        $this->step = 'otp';
        $this->otp = '';
        session()->put('submission.flow_step', 'otp');
        $this->advanceToOtpIfPending();
    }

    protected function otpPurpose(): OtpPurpose
    {
        return OtpPurpose::Submit;
    }

    protected function otpTrackingCode(): ?string
    {
        return null;
    }

    protected function otpMobile(): string
    {
        return $this->mobile;
    }

    protected function advanceToOtpIfPending(): void
    {
        if ($this->step !== 'phone' || ! preg_match('/^09\d{9}$/', $this->mobile)) {
            return;
        }

        if (! $this->hasPendingOtpForContext()) {
            return;
        }

        $this->step = 'otp';
    }

    public function render()
    {
        return view('livewire.public.submission-otp-flow');
    }
}
