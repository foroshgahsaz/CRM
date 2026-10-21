@extends('public.layout')

@section('bare_main')
@endsection

@section('content')
    <section class="crm-hero p-6 sm:p-10 lg:p-12">
        <div class="relative z-10 mx-auto max-w-3xl text-center">
            <span class="crm-badge">پورتال هوشمند مشتریان</span>
            <h1 class="mt-5 text-3xl font-extrabold leading-tight text-white sm:text-5xl">{{ $company['company_name'] }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-white/85 sm:text-lg">{{ $company['company_description'] }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('requests.create') }}" class="crm-btn-primary">شروع ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="crm-btn-secondary">پیگیری با کد رهگیری</a>
            </div>
        </div>

        <div class="relative z-10 mt-10 flex flex-col items-center">
            <svg class="crm-hero-radar crm-hero-radar-center" viewBox="0 0 200 200" fill="none" aria-hidden="true">
                <circle cx="100" cy="100" r="90" stroke="white" stroke-opacity="0.25" stroke-width="1"/>
                <circle cx="100" cy="100" r="60" stroke="white" stroke-opacity="0.2" stroke-width="1"/>
                <circle cx="100" cy="100" r="30" stroke="white" stroke-opacity="0.35" stroke-width="1"/>
                <line x1="100" y1="100" x2="175" y2="55" stroke="white" stroke-opacity="0.3"/>
                <circle cx="175" cy="55" r="4" fill="#38bdf8"/>
                <circle cx="130" cy="140" r="3" fill="#a78bfa"/>
            </svg>

            <div class="mt-2 grid w-full gap-3 md:grid-cols-3">
                @foreach ([
                    ['۱', 'تأیید موبایل', 'شماره خود را وارد کنید و رمز یک‌بارمصرف را دریافت کنید.'],
                    ['۲', 'تکمیل فرم', 'نوع درخواست، اطلاعات خرید و مدارک را ثبت کنید.'],
                    ['۳', 'پیگیری امن', 'با کد رهگیری فقط وضعیت همان پرونده را ببینید.'],
                ] as [$step, $title, $text])
                    <div class="crm-step-pill text-right">
                        <span class="crm-step-num">{{ $step }}</span>
                        <span><strong class="font-semibold text-white">{{ $title }}</strong> — {{ $text }}</span>
                    </div>
                @endforeach
            </div>
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
