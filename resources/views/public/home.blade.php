@extends('public.layout')

@section('bare_main')
@endsection

@section('content')
    <section class="crm-hero-premium p-6 sm:p-10 lg:p-12">
        <div class="relative z-10 mx-auto max-w-3xl text-center">
            <span class="crm-badge">پورتال پریمیوم مشتریان</span>
            <h1 class="mt-5 text-3xl font-extrabold leading-tight text-[#1e4d3e] sm:text-5xl">{{ $company['company_name'] }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-[#5c7268] sm:text-lg">{{ $company['company_description'] }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('requests.create') }}" class="crm-btn-primary">شروع ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="crm-btn-secondary">پیگیری با کد رهگیری</a>
            </div>
        </div>

        <div class="relative z-10 mt-8 sm:mt-12">
            @include('public.partials.orbit-hero')
        </div>

        <div class="relative z-10 mt-10 grid w-full gap-3 md:grid-cols-3">
            @foreach ([
                ['۱', 'تأیید موبایل', 'شماره خود را وارد کنید و رمز یک‌بارمصرف را دریافت کنید.'],
                ['۲', 'تکمیل فرم', 'نوع درخواست، اطلاعات خرید و مدارک را ثبت کنید.'],
                ['۳', 'پیگیری', 'با کد رهگیری فقط وضعیت همان پرونده را ببینید.'],
            ] as [$step, $title, $text])
                <div class="crm-step-pill text-right">
                    <span class="crm-step-num">{{ $step }}</span>
                    <span><strong class="font-semibold text-[#1e4d3e]">{{ $title }}</strong> — {{ $text }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-8 grid gap-5 lg:grid-cols-2">
        <article class="crm-card-muted p-7 sm:p-8">
            <h2 class="text-xl font-bold text-[#1e4d3e]">روند رسیدگی</h2>
            <p class="mt-4 text-sm leading-8 text-[#5c7268]">{{ $company['process_guide'] }}</p>
        </article>
        <article class="crm-card crm-card-glow p-7 sm:p-8">
            <h2 class="text-xl font-bold text-[#1e4d3e]">مدارک مورد نیاز</h2>
            <p class="mt-4 text-sm leading-8 text-[#5c7268]">{{ $company['required_documents_text'] }}</p>
        </article>
    </section>
@endsection
