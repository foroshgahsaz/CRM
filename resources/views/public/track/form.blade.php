@extends('public.layout')

@section('content')
    @if ($case)
        @include('public.track.case', ['case' => $case])
    @else
        <div class="crm-card mx-auto grid max-w-xl gap-6 p-6 sm:p-8">
            <div>
                <h1 class="text-2xl font-bold text-brand-900">پیگیری درخواست</h1>
                <p class="mt-2 text-sm leading-7 text-slate-600">کد رهگیری را وارد کنید. رمز یک‌بارمصرف فقط به شماره ثبت‌شده در همان پرونده ارسال می‌شود.</p>
            </div>
            <form method="POST" action="{{ route('track.otp.send') }}" class="grid gap-3">
                @csrf
                <label class="grid gap-1 text-sm font-medium text-brand-900" for="tracking_code">کد رهگیری
                    <input id="tracking_code" name="tracking_code" value="{{ old('tracking_code') }}" class="crm-input font-normal" dir="ltr" required>
                </label>
                <button class="crm-btn-solid">ارسال رمز یک‌بارمصرف</button>
            </form>
            @if (session('tracking.pending_code'))
                <form method="POST" action="{{ route('track.otp.verify') }}" class="grid gap-3 border-t border-indigo-100 pt-4">
                    @csrf
                    <label class="grid gap-1 text-sm font-medium text-brand-900" for="code">رمز یک‌بارمصرف
                        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="crm-input font-normal" required>
                    </label>
                    <button type="submit" class="rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 font-semibold text-brand-800">مشاهده وضعیت</button>
                </form>
            @endif
        </div>
    @endif
@endsection
