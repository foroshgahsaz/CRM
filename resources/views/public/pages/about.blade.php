@extends('public.layout')

@section('content')
    <article class="hs-panel max-w-3xl">
        <h1 class="hs-panel-title">درباره {{ $company['company_name'] }}</h1>
        <p class="hs-panel-lead">{{ $company['company_tagline'] }}</p>

        <div class="prose-crm mt-6 space-y-4 text-sm leading-8 text-slate-700">
            <p>{{ $company['company_description'] }}</p>
            <p>
                این وب‌سایت درگاه رسمی ثبت و پیگیری درخواست مشتریان است. کاربران پس از تأیید شماره موبایل (رمز یک‌بارمصرف)،
                درخواست خود را ثبت می‌کنند و با کد رهگیری وضعیت پرونده را مشاهده می‌کنند.
            </p>
            <h2 class="text-lg font-bold text-brand-900">خدمات</h2>
            <ul class="list-disc pe-6">
                <li>ثبت درخواست پس از خرید (شکستگی، کمبود، مغایرت و …)</li>
                <li>پیگیری با کد رهگیری</li>
                <li>بارگذاری مدارک و تکمیل اطلاعات</li>
            </ul>
            <p class="text-slate-600">
                <a href="{{ route('requests.create') }}" class="font-semibold text-brand-700 underline">ثبت درخواست</a>
                ·
                <a href="{{ route('track.show') }}" class="font-semibold text-brand-700 underline">پیگیری</a>
            </p>
        </div>
    </article>
@endsection
