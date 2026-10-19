@extends('public.layout')

@section('content')
    <section class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <p class="text-sm font-medium text-brand-700">خدمات مشتریان</p>
            <h1 class="mt-2 text-3xl font-bold text-stone-900">{{ $company['company_name'] }}</h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-stone-600">{{ $company['company_description'] }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('requests.create') }}" class="rounded-lg bg-brand-700 px-5 py-3 font-medium text-white hover:bg-brand-800">ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="rounded-lg border border-stone-300 px-5 py-3 font-medium text-stone-800 hover:bg-stone-50">پیگیری درخواست</a>
            </div>
        </div>
        <aside class="rounded-2xl bg-stone-900 p-6 text-stone-100">
            <h2 class="text-lg font-semibold">روند رسیدگی</h2>
            <p class="mt-3 text-sm leading-7 text-stone-300">{{ $company['process_guide'] }}</p>
        </aside>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-3">
        @foreach ([
            ['۱', 'تأیید موبایل', 'شماره خود را وارد کنید و رمز یک‌بارمصرف را بنویسید.'],
            ['۲', 'تکمیل فرم', 'نوع درخواست، اطلاعات خرید و مدارک را ثبت کنید.'],
            ['۳', 'پیگیری امن', 'با کد رهگیری و رمز تازه، فقط وضعیت همان پرونده را ببینید.'],
        ] as [$step, $title, $text])
            <article class="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                <p class="text-sm font-bold text-brand-700">مرحله {{ $step }}</p>
                <h2 class="mt-2 font-semibold text-stone-900">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">{{ $text }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
        <h2 class="text-lg font-semibold text-stone-900">مدارک مورد نیاز</h2>
        <p class="mt-2 text-sm leading-7 text-stone-600">{{ $company['required_documents_text'] }}</p>
    </section>
@endsection
