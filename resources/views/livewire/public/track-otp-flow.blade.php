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

    @error('otp')
        <div class="crm-alert-error mb-4 px-4 py-3 text-sm" role="alert">{{ $message }}</div>
    @enderror

    @if ($step === 'tracking')
        <form wire:submit="sendOtp" class="crm-form-section grid gap-3">
            <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="track-code">کد رهگیری</label>
            <input
                id="track-code"
                type="text"
                wire:model="trackingCode"
                dir="ltr"
                autocomplete="off"
                class="crm-input tracking-widest"
                placeholder="RQ-XXXXXXXXXX"
                autofocus
                @disabled($otpCooldownBlocksSend)
                required
            >
            @error('trackingCode')
                @if ($otpResendRemaining <= 0)
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @endif
            @enderror

            @if ($otpResendRemaining > 0 && $caseMobile)
                @include('livewire.public.otp-resend-timer', [
                    'remaining' => $otpResendRemaining,
                    'showResendButton' => false,
                ])
            @endif

            @if ($hasPendingOtp && $otpResendRemaining > 0)
                <p class="rounded-xl bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-800">
                    کد قبلاً ارسال شده است. برای وارد کردن رمز ادامه دهید.
                </p>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                @disabled($otpCooldownBlocksSend)
                class="crm-btn-solid disabled:cursor-not-allowed disabled:opacity-60"
            >
                @if ($hasPendingOtp && $otpResendRemaining > 0)
                    <span wire:loading.remove wire:target="sendOtp">ادامه — وارد کردن رمز</span>
                @else
                    <span wire:loading.remove wire:target="sendOtp">ارسال رمز یک‌بارمصرف</span>
                @endif
                <span wire:loading wire:target="sendOtp">در حال ارسال...</span>
            </button>
        </form>
    @else
        <form wire:submit="verifyOtp" class="crm-form-section grid gap-3">
            <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">
                کد رهگیری <strong dir="ltr">{{ $trackingCode }}</strong> — رمز به شماره ثبت‌شده در پرونده ارسال شد.
                <button type="button" wire:click="backToTracking" class="mr-2 font-bold text-brand-700 hover:text-brand-900">ویرایش کد</button>
            </div>

            <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="track-otp">رمز یک‌بارمصرف</label>
            <input
                id="track-otp"
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

            <button type="submit" wire:loading.attr="disabled" class="crm-btn-outline">
                <span wire:loading.remove wire:target="verifyOtp">مشاهده وضعیت</span>
                <span wire:loading wire:target="verifyOtp">در حال بررسی...</span>
            </button>

            @include('livewire.public.otp-resend-timer', [
                'remaining' => $otpResendRemaining,
                'showResendButton' => true,
            ])
        </form>
    @endif
</div>
