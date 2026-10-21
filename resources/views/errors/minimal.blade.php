<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="crm-page-bg grid min-h-screen place-items-center p-4">
    <div class="crm-ambient" aria-hidden="true">
        <span class="crm-orb crm-orb-a"></span>
        <span class="crm-orb crm-orb-b"></span>
    </div>
    <main class="crm-card crm-card-glow relative z-10 w-full max-w-md p-8 text-center sm:p-10">
        <p class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-sm font-bold text-brand-700">{{ $code }}</p>
        <h1 class="mt-4 text-2xl font-extrabold text-brand-900">{{ $title }}</h1>
        <p class="mt-4 leading-8 text-slate-600">{{ $message }}</p>
        @if ($code === 419)
            <p class="mt-3 text-xs leading-6 text-slate-500">اگر سایت را با HTTP باز کرده‌اید، پس از deploy جدید یک‌بار صفحه را رفرش کنید. در Runflare ترجیحاً HTTPS فعال باشد.</p>
        @endif
        <a href="{{ url('/') }}" class="crm-btn-solid mt-7 inline-flex w-full justify-center sm:w-auto">بازگشت به صفحه اصلی</a>
    </main>
</body>
</html>
