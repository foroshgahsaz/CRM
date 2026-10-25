@extends('public.layout')

@section('content')
    <article class="hs-panel max-w-3xl">
        <h1 class="hs-panel-title">حریم خصوصی و پیامک اطلاع‌رسانی</h1>
        <p class="hs-panel-lead">{{ $company['company_name'] }} — {{ config('app.url') }}</p>

        <div class="prose-crm mt-6 space-y-4 text-sm leading-8 text-slate-700">
            <h2 class="text-lg font-bold text-brand-900">۱. هدف سامانه</h2>
            <p>
                این سایت برای ثبت درخواست مشتریان، پیگیری پرونده و اطلاع‌رسانی وضعیت رسیدگی استفاده می‌شود.
                بخش مدیریت داخلی (/admin) فقط برای کارکنان مجاز است.
            </p>

            <h2 class="text-lg font-bold text-brand-900">۲. پیامک رمز یک‌بارمصرف (OTP)</h2>
            <p>
                برای ورود امن به فرم ثبت درخواست، پیگیری پرونده و (در صورت فعال‌سازی) ورود کارکنان،
                رمز یک‌بارمصرف به شماره موبایل ثبت‌شده ارسال می‌شود. متن پیامک شامل نام سامانه و کد تأیید است
                و برای احراز هویت کاربر استفاده می‌شود، نه بازاریابی انبوه.
            </p>
            <p class="rounded-xl bg-[#e2ede6] px-4 py-3 text-brand-900">
                نمونه متن: «{{ $company['company_name'] }}: رمز تأیید شما XXXXXX است. این رمز را در اختیار دیگران نگذارید.»
            </p>

            <h2 class="text-lg font-bold text-brand-900">۳. داده‌های جمع‌آوری‌شده</h2>
            <ul class="list-disc pe-6">
                <li>شماره موبایل (برای OTP و ارتباط درباره همان پرونده)</li>
                <li>اطلاعات فرم درخواست و مدارک بارگذاری‌شده</li>
                <li>کد رهگیری برای پیگیری</li>
            </ul>

            <h2 class="text-lg font-bold text-brand-900">۴. نگهداری و امنیت</h2>
            <p>
                رمز OTP به‌صورت هش ذخیره می‌شود. دسترسی به پرونده‌ها محدود به مالک موبایل (OTP) و کارکنان مجاز است.
            </p>

            <h2 class="text-lg font-bold text-brand-900">۵. تماس</h2>
            <p>
                @if ($company['contact_email'])
                    ایمیل: <span dir="ltr">{{ $company['contact_email'] }}</span>
                @endif
                @if ($company['contact_phone'])
                    · تلفن: <span dir="ltr">{{ $company['contact_phone'] }}</span>
                @endif
                · <a href="{{ route('site.contact') }}" class="font-semibold text-brand-700 underline">صفحه تماس</a>
            </p>
        </div>
    </article>
@endsection
