<?php

namespace App\Livewire\Public;

use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Livewire\Public\Concerns\InteractsWithOtpResendTimer;
use App\Models\ServiceRequest;
use App\Services\CustomerAccessService;
use App\Services\OtpService;
use App\Support\MobileNumber;
use App\Support\TrackingCode;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TrackOtpFlow extends Component
{
    use InteractsWithOtpResendTimer;

    public string $step = 'tracking';

    public string $trackingCode = '';

    public string $otp = '';

    /** @var string|null موبایل پرونده (پس از یافتن درخواست) */
    public ?string $caseMobile = null;

    public function mount(): void
    {
        $pending = session('tracking.pending_code');

        if (is_string($pending) && $pending !== '') {
            $this->trackingCode = $pending;
            $this->resolveCaseMobile();
            $this->advanceToOtpIfPending();
        }
    }

    public function hydrate(): void
    {
        $this->resolveCaseMobile();
        $this->advanceToOtpIfPending();
    }

    public function updatedTrackingCode(): void
    {
        $this->trackingCode = TrackingCode::normalize($this->trackingCode);
        $this->resolveCaseMobile();
        $this->advanceToOtpIfPending();
    }

    public function sendOtp(OtpService $otp): void
    {
        $this->trackingCode = TrackingCode::normalize($this->trackingCode);

        $this->validate([
            'trackingCode' => ['required', 'string', 'min:6', 'max:32'],
        ], [
            'trackingCode.required' => 'کد رهگیری را وارد کنید.',
        ]);

        $serviceRequest = ServiceRequest::query()
            ->where('tracking_code', $this->trackingCode)
            ->with('customer')
            ->first();

        if ($serviceRequest === null) {
            session()->forget('tracking.pending_code');
            session()->flash('status', 'اگر کد رهگیری معتبر باشد، رمز یک‌بارمصرف به شماره ثبت‌شده ارسال می‌شود.');

            return;
        }

        $this->caseMobile = $serviceRequest->customer->mobile;
        $rateKey = 'track-otp-send:'.$this->trackingCode;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages([
                'trackingCode' => 'تلاش‌های زیاد. لطفاً بعداً دوباره امتحان کنید.',
            ]);
        }

        if ($otp->resendCooldownRemainingSeconds($this->caseMobile, OtpPurpose::Track, $serviceRequest->tracking_code) > 0) {
            session()->put('tracking.pending_code', $serviceRequest->tracking_code);
            $this->trackingCode = $serviceRequest->tracking_code;
            $this->advanceToOtpIfPending();

            return;
        }

        try {
            $otp->issue($this->caseMobile, OtpPurpose::Track, $serviceRequest->tracking_code, request()->ip());
        } catch (WorkflowException $exception) {
            if ($otp->hasPendingOtp($this->caseMobile, OtpPurpose::Track, $serviceRequest->tracking_code)) {
                session()->put('tracking.pending_code', $serviceRequest->tracking_code);
                $this->trackingCode = $serviceRequest->tracking_code;
                $this->step = 'otp';

                throw ValidationException::withMessages([
                    'otp' => $exception->getMessage(),
                ]);
            }

            throw ValidationException::withMessages([
                'trackingCode' => $exception->getMessage(),
            ]);
        }

        RateLimiter::hit($rateKey, 300);
        session()->put('tracking.pending_code', $serviceRequest->tracking_code);
        $this->trackingCode = $serviceRequest->tracking_code;
        $this->step = 'otp';
        $this->otp = '';
        session()->flash('otp_status', 'کد تأیید به شماره ثبت‌شده در پرونده ارسال شد.');
    }

    public function verifyOtp(OtpService $otp, CustomerAccessService $access): void
    {
        $this->trackingCode = TrackingCode::normalize($this->trackingCode);
        $this->otp = MobileNumber::toEnglishDigits(trim($this->otp));
        $this->resolveCaseMobile();

        $serviceRequest = ServiceRequest::query()
            ->where('tracking_code', $this->trackingCode)
            ->with('customer')
            ->first();

        if ($serviceRequest === null || $this->caseMobile === null) {
            throw ValidationException::withMessages([
                'otp' => 'رمز یک‌بارمصرف نامعتبر یا منقضی شده است.',
            ]);
        }

        $length = $otp->otpLength();

        $this->validate([
            'otp' => ['required', 'digits:'.$length],
        ], [
            'otp.digits' => "کد تأیید باید {$length} رقم باشد.",
        ]);

        $rateKey = 'track-otp-verify:'.$this->trackingCode;

        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'تلاش‌های زیاد. لطفاً کد جدید درخواست کنید.',
            ]);
        }

        try {
            $otp->verify(
                $serviceRequest->customer->mobile,
                OtpPurpose::Track,
                $serviceRequest->tracking_code,
                $this->otp,
            );
        } catch (WorkflowException $exception) {
            RateLimiter::hit($rateKey, 120);
            throw ValidationException::withMessages([
                'otp' => $exception->getMessage(),
            ]);
        }

        RateLimiter::clear($rateKey);
        session()->forget('tracking.pending_code');
        $cookie = $access->start($serviceRequest, request());
        session()->put('customer_access_bootstrap', $cookie->getValue());
        session()->regenerate();

        $this->redirectRoute('track.access.bootstrap', navigate: false);
    }

    public function backToTracking(): void
    {
        $this->step = 'tracking';
        $this->otp = '';
        session()->forget('tracking.pending_code');
        $this->resetValidation();
    }

    protected function otpPurpose(): OtpPurpose
    {
        return OtpPurpose::Track;
    }

    protected function otpTrackingCode(): ?string
    {
        return $this->trackingCode !== '' ? $this->trackingCode : null;
    }

    protected function otpMobile(): string
    {
        return $this->caseMobile ?? '';
    }

    protected function advanceToOtpIfPending(): void
    {
        if ($this->step !== 'tracking' || $this->caseMobile === null) {
            return;
        }

        if (! $this->hasPendingOtpForContext()) {
            return;
        }

        $this->step = 'otp';
    }

    protected function resolveCaseMobile(): void
    {
        if ($this->trackingCode === '') {
            $this->caseMobile = null;

            return;
        }

        $serviceRequest = ServiceRequest::query()
            ->where('tracking_code', $this->trackingCode)
            ->with('customer')
            ->first();

        $this->caseMobile = $serviceRequest?->customer?->mobile;
    }

    public function render()
    {
        return view('livewire.public.track-otp-flow');
    }
}
