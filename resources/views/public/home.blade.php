@extends('public.layout')

@section('content')
    <section class="crm-hero p-6 sm:p-10 lg:p-12">
        <svg class="crm-hero-radar hidden sm:block" viewBox="0 0 200 200" fill="none" aria-hidden="true">
            <circle cx="100" cy="100" r="90" stroke="white" stroke-opacity="0.25" stroke-width="1"/>
            <circle cx="100" cy="100" r="60" stroke="white" stroke-opacity="0.2" stroke-width="1"/>
            <circle cx="100" cy="100" r="30" stroke="white" stroke-opacity="0.35" stroke-width="1"/>
            <line x1="100" y1="100" x2="175" y2="55" stroke="white" stroke-opacity="0.3"/>
            <circle cx="175" cy="55" r="4" fill="#38bdf8"/>
            <circle cx="130" cy="140" r="3" fill="#a78bfa"/>
        </svg>

        <div class="relative z-10 max-w-2xl pe-0 sm:pe-32">
            <span class="crm-badge">خدمات مشتریان</span>
            <h1 class="mt-4 text-3xl font-extrabold leading-tight text-white sm:text-4xl">{{ $company['company_name'] }}</h1>
            <p class="mt-4 max-w-xl text-base leading-8 text-white/85">{{ $company['company_description'] }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('requests.create') }}" class="crm-btn-primary">ثبت درخواست جدید</a>
                <a href="{{ route('track.show') }}" class="crm-btn-secondary">پیگیری با کد رهگیری</a>
            </div>
        </div>

        <div class="relative z-10 mt-10 grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['۱', 'تأیید موبایل', 'شماره خود را وارد کنید و رمز یک‌بارمصرف را دریافت کنید.'],
                ['۲', 'تکمیل فرم', 'نوع درخواست، اطلاعات خرید و مدارک را ثبت کنید.'],
                ['۳', 'پیگیری امن', 'با کد رهگیری فقط وضعیت همان پرونده را ببینید.'],
            ] as [$step, $title, $text])
                <div class="crm-step-pill">
                    <span class="crm-step-num">{{ $step }}</span>
                    <span><strong class="font-semibold">{{ $title }}</strong> — {{ $text }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        <article class="crm-card-muted p-6">
            <h2 class="text-lg font-bold text-white">روند رسیدگی</h2>
            <p class="mt-3 text-sm leading-8 text-white/80">{{ $company['process_guide'] }}</p>
        </article>
        <article class="crm-card p-6">
            <h2 class="text-lg font-bold text-brand-900">مدارک مورد نیاز</h2>
            <p class="mt-3 text-sm leading-8 text-slate-600">{{ $company['required_documents_text'] }}</p>
        </article>
    </section>
@endsection
