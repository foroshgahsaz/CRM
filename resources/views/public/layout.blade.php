<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="jalali-today" content="{{ \App\Support\JalaliDate::formatDate(now()) }}">
    <title>{{ isset($title) ? $title.' | '.$company['company_name'] : $company['company_name'] }}</title>
    <meta name="description" content="{{ $metaDescription ?? $company['company_tagline'] }}">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    @include('partials.jalali-datepicker-assets')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="crm-page-bg min-h-screen antialiased">
    <div class="crm-ambient" aria-hidden="true">
        <span class="crm-ripple crm-ripple-a"></span>
        <span class="crm-ripple crm-ripple-b"></span>
        <span class="crm-ripple crm-ripple-c"></span>
    </div>

    <header class="crm-header-glass sticky top-0 z-40 border-b">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:py-4">
            <a href="{{ route('home') }}" class="group flex items-center gap-3">
                @include('public.partials.brand-mark')
                <span>
                    <span class="block text-base font-bold tracking-tight text-[#1e4d3e]">{{ $company['company_name'] }}</span>
                    <span class="block text-xs sm:text-sm text-[#5c7268]">{{ $company['company_tagline'] }}</span>
                </span>
            </a>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('home') }}" class="crm-nav-link hidden sm:inline-flex">خانه</a>
                <a href="{{ route('requests.create') }}" class="crm-btn-primary px-4 py-2 text-sm">ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="crm-btn-secondary px-4 py-2 text-sm">پیگیری</a>
            </nav>
        </div>
    </header>

    <main @class([
        'relative z-10 mx-auto max-w-6xl px-4 py-8 sm:py-10 md:px-8 md:py-10',
        'crm-content-area' => ! View::hasSection('bare_main'),
    ])>
        @if (session('status'))
            <div class="crm-alert-success mb-5 px-4 py-3 text-sm shadow-lg" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="crm-alert-error mb-5 px-4 py-3 text-sm shadow-lg" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="crm-alert-error mb-5 px-4 py-3 text-sm shadow-lg" role="alert">
                <p class="font-semibold">اطلاعات ناقص یا نادرست است.</p>
                <ul class="mt-2 list-disc pe-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="crm-footer-glass relative z-10 mt-10 border-t">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-[#5c7268] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                @include('public.partials.brand-mark', ['imgClass' => 'h-9 w-9 rounded-xl object-cover ring-1 ring-[#e2ede6]'])
                <p class="font-medium text-[#1e4d3e]">{{ $company['company_name'] }}</p>
            </div>
            <nav class="flex flex-wrap gap-x-4 gap-y-2 text-[#3d5a50]">
                <a href="{{ route('site.about') }}" class="hover:text-[#1e4d3e]">درباره ما</a>
                <a href="{{ route('site.contact') }}" class="hover:text-[#1e4d3e]">تماس</a>
                <a href="{{ route('site.privacy') }}" class="hover:text-[#1e4d3e]">حریم خصوصی و OTP</a>
            </nav>
            <div class="flex flex-col gap-1 sm:text-left">
                @if ($company['contact_phone'])<span>تلفن: {{ $company['contact_phone'] }}</span>@endif
                @if ($company['contact_email'])<span>ایمیل: {{ $company['contact_email'] }}</span>@endif
                @if ($company['contact_address'])<span>{{ $company['contact_address'] }}</span>@endif
            </div>
        </div>
    </footer>
</body>
</html>
