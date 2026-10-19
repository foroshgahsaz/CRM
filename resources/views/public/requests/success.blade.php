@extends('public.layout')

@section('content')
    <section class="mx-auto max-w-xl rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-stone-200 sm:p-8">
        <p class="text-sm font-medium text-brand-700">درخواست ثبت شد</p>
        <h1 class="mt-2 text-2xl font-bold text-stone-900">کد رهگیری شما</h1>
        <p class="mt-4 rounded-xl bg-stone-900 px-4 py-5 text-2xl font-bold tracking-widest text-white" dir="ltr">{{ $tracking_code }}</p>
        <p class="mt-4 text-sm leading-7 text-stone-600">این کد را نگه دارید. برای پیگیری، به صفحه پیگیری بروید و رمز یک‌بارمصرف تازه‌ای دریافت کنید.</p>
        @if ($sms)
            <p class="mt-2 text-sm text-stone-600">پیامک تأیید در صورت فعال‌بودن ارسال، به شماره شما فرستاده می‌شود. نتیجه ارسال در گزارش پیامک قابل بررسی است.</p>
        @endif
        <a href="{{ route('track.show') }}" class="mt-6 inline-block rounded-lg bg-brand-700 px-5 py-3 font-medium text-white">پیگیری درخواست</a>
    </section>
@endsection
