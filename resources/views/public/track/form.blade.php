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
            <form method="POST" action="{{ route('track.otp.send') }}" class="crm-form-section grid gap-3">
                @csrf
                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="tracking_code">کد رهگیری
                    <input id="tracking_code" name="tracking_code" value="{{ old('tracking_code') }}" class="crm-input font-normal tracking-widest" dir="ltr" required>
                </label>
                <button class="crm-btn-solid">ارسال رمز یک‌بارمصرف</button>
            </form>
            @if (session('tracking.pending_code'))
                <form method="POST" action="{{ route('track.otp.verify') }}" class="crm-form-section grid gap-3">
                    @csrf
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="code">رمز یک‌بارمصرف
                        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="crm-input font-normal" required>
                    </label>
                    <button type="submit" class="crm-btn-outline">مشاهده وضعیت</button>
                </form>
            @endif
        </div>
    @endif
@endsection
