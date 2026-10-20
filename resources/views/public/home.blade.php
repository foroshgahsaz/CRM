@extends('public.layout')

@section('content')
    <section class="crm-hero p-6 sm:p-10 lg:p-12">
        <svg class="crm-hero-radar hidden lg:block" viewBox="0 0 200 200" fill="none" aria-hidden="true">
            <circle cx="100" cy="100" r="90" stroke="white" stroke-opacity="0.25" stroke-width="1"/>
            <circle cx="100" cy="100" r="60" stroke="white" stroke-opacity="0.2" stroke-width="1"/>
            <circle cx="100" cy="100" r="30" stroke="white" stroke-opacity="0.35" stroke-width="1"/>
            <line x1="100" y1="100" x2="175" y2="55" stroke="white" stroke-opacity="0.3"/>
            <circle cx="175" cy="55" r="4" fill="#38bdf8"/>
            <circle cx="130" cy="140" r="3" fill="#a78bfa"/>
        </svg>

        <div class="relative z-10 grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div class="max-w-2xl pe-0 lg:pe-8">
                <span class="crm-badge">پورتال هوشمند مشتریان</span>
                <h1 class="mt-5 text-3xl font-extrabold leading-tight text-white sm:text-5xl">{{ $company['company_name'] }}</h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/85 sm:text-lg">{{ $company['company_description'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('requests.create') }}" class="crm-btn-primary">شروع ثبت درخواست</a>
                    <a href="{{ route('track.show') }}" class="crm-btn-secondary">پیگیری با کد رهگیری</a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                <div class="crm-stat">
                    <span class="text-xs text-white/70">مرحله ۱</span>
                    <strong>OTP امن</strong>
                    <p class="mt-1 text-xs leading-6 text-white/75">تأیید موبایل قبل از ثبت</p>
                </div>
                <div class="crm-stat">
                    <span class="text-xs text-white/70">مرحله ۲</span>
                    <strong>فرم پویا</strong>
                    <p class="mt-1 text-xs leading-6 text-white/75">انواع درخواست و مدارک</p>
                </div>
                <div class="crm-stat">
                    <span class="text-xs text-white/70">مرحله ۳</span>
                    <strong>پیگیری ۲۴/۷</strong>
                    <p class="mt-1 text-xs leading-6 text-white/75">وضعیت شفاف پرونده</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-10 grid gap-3 md:grid-cols-3">
            @foreach ([
                ['۱', 'تأیید موبایل', 'شماره خود را وارد کنید و رمز یک‌بارمصرف را دریافت کنید.'],
                ['۲', 'تکمیل فرم', 'نوع درخواست، اطلاعات خرید و مدارک را ثبت کنید.'],
                ['۳', 'پیگیری امن', 'با کد رهگیری فقط وضعیت همان پرونده را ببینید.'],
            ] as [$step, $title, $text])
                <div class="crm-step-pill">
                    <span class="crm-step-num">{{ $step }}</span>
                    <span><strong class="font-semibold text-white">{{ $title }}</strong> — {{ $text }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-8 grid gap-5 lg:grid-cols-2">
        <article class="crm-card-muted p-7 sm:p-8">
            <h2 class="text-xl font-bold text-white">روند رسیدگی</h2>
            <p class="mt-4 text-sm leading-8 text-white/82">{{ $company['process_guide'] }}</p>
        </article>
        <article class="crm-card crm-card-glow p-7 sm:p-8">
            <h2 class="text-xl font-bold text-brand-900">مدارک مورد نیاز</h2>
            <p class="mt-4 text-sm leading-8 text-slate-600">{{ $company['required_documents_text'] }}</p>
        </article>
    </section>
@endsection
