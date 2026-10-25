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
<body class="hs-site min-h-screen antialiased">
    <header class="hs-header">
        <div class="hs-container hs-header-inner">
            <a href="{{ route('home') }}" class="hs-brand">
                @include('public.partials.brand-mark', ['imgClass' => 'h-9 w-9 shrink-0 rounded-xl object-cover sm:h-10 sm:w-10'])
                <span class="hs-brand-copy">
                    <span class="hs-brand-title">{{ $company['company_name'] }}</span>
                    <span class="hs-brand-sub">{{ $company['company_tagline'] }}</span>
                </span>
            </a>
            <nav class="hs-nav" aria-label="منوی اصلی">
                <a href="{{ route('home') }}">خانه</a>
                <a href="{{ route('home') }}#process">روند رسیدگی</a>
                <a href="{{ route('home') }}#faq">سوالات</a>
                <a href="{{ route('site.contact') }}">تماس</a>
            </nav>
            <div class="hs-header-cta">
                <a href="{{ route('requests.create') }}" class="hs-btn hs-btn-primary">
                    <span class="sm:hidden">ثبت</span>
                    <span class="hidden sm:inline">ثبت درخواست</span>
                </a>
            </div>
        </div>
    </header>

    <main class="relative z-10">
        @php($hasLanding = View::hasSection('bare_main'))
        <div @class(['hs-container' => true, 'pt-4' => $hasLanding, 'hs-main-inner' => ! $hasLanding])>
            @if (session('status'))
                <div class="hs-alert hs-alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="hs-alert hs-alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="hs-alert hs-alert-error" role="alert">
                    <p class="font-semibold">اطلاعات ناقص یا نادرست است.</p>
                    <ul class="mt-2 list-disc pe-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if ($hasLanding)
            @yield('content')
        @else
            <div class="hs-container hs-main-inner">
                @yield('content')
            </div>
        @endif
    </main>

    <footer class="hs-footer">
        <div class="hs-container hs-footer-inner">
            <a href="{{ route('home') }}">← بازگشت به صفحه اصلی</a>
            <p>© {{ now()->year }} {{ $company['company_name'] }} — تمامی حقوق محفوظ است.</p>
        </div>
    </footer>
</body>
</html>
