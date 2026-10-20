@extends('public.layout')

@section('content')
    <section class="crm-card mx-auto max-w-xl p-6 text-center sm:p-8">
        <p class="text-sm font-semibold text-brand-600">درخواست ثبت شد</p>
        <h1 class="mt-2 text-2xl font-bold text-brand-900">کد رهگیری شما</h1>
        <p class="crm-tracking-code mt-4" dir="ltr">{{ $tracking_code }}</p>
        <p class="mt-4 text-sm leading-7 text-slate-600">این کد را نگه دارید. برای پیگیری، به صفحه پیگیری بروید و رمز یک‌بارمصرف تازه‌ای دریافت کنید.</p>
        @if ($sms)
            <p class="mt-2 text-sm text-slate-600">پیامک تأیید در صورت فعال‌بودن ارسال، به شماره شما فرستاده می‌شود.</p>
        @endif
        <a href="{{ route('track.show') }}" class="crm-btn-solid mt-6 inline-flex">پیگیری درخواست</a>
    </section>
@endsection
