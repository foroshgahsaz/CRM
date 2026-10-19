@extends('public.layout')

@section('content')
    @if ($case)
        @include('public.track.case', ['case' => $case])
    @else
        <div class="mx-auto grid max-w-xl gap-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">پیگیری درخواست</h1>
                <p class="mt-2 text-sm leading-7 text-stone-600">کد رهگیری را وارد کنید. رمز یک‌بارمصرف فقط به شماره ثبت‌شده در همان پرونده ارسال می‌شود.</p>
            </div>
            <form method="POST" action="{{ route('track.otp.send') }}" class="grid gap-3">
                @csrf
                <label class="grid gap-1 text-sm font-medium" for="tracking_code">کد رهگیری
                    <input id="tracking_code" name="tracking_code" value="{{ old('tracking_code') }}" class="rounded-lg border border-stone-300 px-3 py-2 font-normal" dir="ltr" required>
                </label>
                <button class="rounded-lg bg-brand-700 px-4 py-2 font-medium text-white">ارسال رمز یک‌بارمصرف</button>
            </form>
            @if (session('tracking.pending_code'))
                <form method="POST" action="{{ route('track.otp.verify') }}" class="grid gap-3 border-t border-stone-200 pt-4">
                    @csrf
                    <label class="grid gap-1 text-sm font-medium" for="code">رمز یک‌بارمصرف
                        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="rounded-lg border border-stone-300 px-3 py-2 font-normal" required>
                    </label>
                    <button class="rounded-lg border border-stone-300 px-4 py-2 font-medium">مشاهده وضعیت</button>
                </form>
            @endif
        </div>
    @endif
@endsection
