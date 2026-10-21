@php
    $otpResendRemaining = $this->otpResendRemainingSeconds();
    $hasPendingOtp = $this->hasPendingOtpForContext();
    $otpCooldownBlocksSend = $otpResendRemaining > 0 && ! $hasPendingOtp;
    $otpLength = app(\App\Services\OtpService::class)->otpLength();
@endphp

<div>
    @if (session('otp_status'))
        <div class="crm-alert-success mb-4 px-4 py-3 text-sm" role="status">{{ session('otp_status') }}</div>
    @endif

    <ol class="flex flex-wrap gap-2 text-sm">
        <li class="{{ $step === 'phone' ? 'crm-pill-active' : 'crm-pill-idle' }}">۱. موبایل</li>
        <li class="{{ $step === 'otp' ? 'crm-pill-active' : 'crm-pill-idle' }}">۲. رمز تأیید</li>
        <li class="crm-pill-idle">۳. فرم و مدارک</li>
    </ol>

    @if ($step === 'phone')
        <form wire:submit="sendOtp" class="crm-form-section mt-6 grid gap-3">
            <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="submission-mobile">شماره موبایل</label>
            <input
                id="submission-mobile"
                type="tel"
                wire:model="mobile"
                dir="ltr"
                inputmode="numeric"
                autocomplete="tel"
                placeholder="09123456789"
                autofocus
                @disabled($otpCooldownBlocksSend)
                class="crm-input disabled:bg-slate-100 disabled:text-slate-500"
            >
            @error('mobile')
                @if ($otpResendRemaining <= 0)
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @endif
            @enderror

            @if ($otpResendRemaining > 0)
                @include('livewire.public.otp-resend-timer', [
                    'remaining' => $otpResendRemaining,
                    'showResendButton' => false,
                ])
            @endif

            @if ($hasPendingOtp && $otpResendRemaining > 0)
                <p class="rounded-xl bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-800">
                    کد قبلاً برای این شماره ارسال شده است. برای وارد کردن کد ادامه دهید.
                </p>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                @disabled($otpCooldownBlocksSend)
                class="crm-btn-solid mt-1 disabled:cursor-not-allowed disabled:opacity-60"
            >
                @if ($hasPendingOtp && $otpResendRemaining > 0)
                    <span wire:loading.remove wire:target="sendOtp">ادامه — وارد کردن کد تأیید</span>
                @else
                    <span wire:loading.remove wire:target="sendOtp">دریافت رمز یک‌بارمصرف</span>
                @endif
                <span wire:loading wire:target="sendOtp">در حال ارسال...</span>
            </button>
        </form>
    @else
        <form wire:submit="verifyOtp" class="crm-form-section mt-6 grid gap-3">
            <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">
                کد به <strong dir="ltr">{{ $mobile }}</strong> ارسال شد.
                <button type="button" wire:click="backToPhone" class="mr-2 font-bold text-brand-700 hover:text-brand-900">ویرایش شماره</button>
            </div>

            <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="submission-otp">رمز یک‌بارمصرف</label>
            <input
                id="submission-otp"
                type="text"
                wire:model="otp"
                dir="ltr"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="{{ $otpLength }}"
                placeholder="{{ str_repeat('·', $otpLength) }}"
                autofocus
                class="crm-input text-center text-lg tracking-[0.45em]"
            >
            @error('otp') <span class="text-sm text-red-600">{{ $message }}</span> @enderror

            <button type="submit" wire:loading.attr="disabled" class="crm-btn-solid mt-1">
                <span wire:loading.remove wire:target="verifyOtp">تأیید شماره</span>
                <span wire:loading wire:target="verifyOtp">در حال بررسی...</span>
            </button>

            @include('livewire.public.otp-resend-timer', [
                'remaining' => $otpResendRemaining,
                'showResendButton' => true,
            ])
        </form>
    @endif
</div>
