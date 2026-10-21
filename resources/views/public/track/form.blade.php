@extends('public.layout')

@section('content')
    @if ($case)
        @include('public.track.case', ['case' => $case])
    @else
        <div class="crm-card crm-card-glow mx-auto grid max-w-xl gap-6 p-6 sm:p-9">
            <div>
                <h1 class="crm-page-title">پیگیری درخواست</h1>
                <p class="crm-page-lead">کد رهگیری را وارد کنید. رمز یک‌بارمصرف فقط به شماره ثبت‌شده در همان پرونده ارسال می‌شود.</p>
            </div>

            @if (! $pendingCode)
                <form method="POST" action="{{ route('track.otp.send') }}" class="crm-form-section grid gap-3">
                    @csrf
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="tracking_code">کد رهگیری
                        <input id="tracking_code" name="tracking_code" value="{{ old('tracking_code') }}" dir="ltr"
                               autocomplete="off" placeholder="RQ-XXXXXXXXXX" class="crm-input font-normal tracking-widest" autofocus required>
                    </label>
                    <button type="submit" class="crm-btn-solid">ارسال رمز یک‌بارمصرف</button>
                </form>
            @else
                <div class="crm-form-section grid gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">
                        <span>کد رهگیری <strong dir="ltr">{{ $pendingCode }}</strong> — رمز به شماره ثبت‌شده در پرونده ارسال شد.</span>
                        <form method="POST" action="{{ route('track.otp.edit') }}">
                            @csrf
                            <button type="submit" class="font-bold text-brand-700 hover:text-brand-900">ویرایش کد</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('track.otp.verify') }}" class="grid gap-3">
                        @csrf
                        <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="code">رمز یک‌بارمصرف</label>
                        <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code"
                               maxlength="{{ $otpLength }}" placeholder="{{ str_repeat('·', $otpLength) }}"
                               class="crm-input text-center text-lg tracking-[0.45em]" autofocus required>
                        <button type="submit" class="crm-btn-solid">مشاهده وضعیت</button>
                    </form>

                    @include('public.partials.otp-resend-timer', [
                        'remaining' => $resendRemaining,
                        'resendAction' => route('track.otp.send'),
                        'resendFields' => ['tracking_code' => $pendingCode],
                    ])
                </div>
            @endif
        </div>
    @endif
@endsection
