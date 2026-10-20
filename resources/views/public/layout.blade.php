<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $company['company_name'] }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/vazirmatn/vazirmatn.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="crm-page-bg min-h-screen antialiased">
    <header class="crm-header-glass border-b">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @if (! empty($company['logo']))
                    <img src="{{ asset('storage/'.$company['logo']) }}" alt="" class="h-11 w-11 rounded-xl object-cover ring-2 ring-white/30">
                @else
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/15 text-sm font-bold text-white ring-1 ring-white/30">درخواست</span>
                @endif
                <span>
                    <span class="block text-base font-bold text-white">{{ $company['company_name'] }}</span>
                    <span class="block text-sm text-white/75">{{ $company['company_tagline'] }}</span>
                </span>
            </a>
            <nav class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('requests.create') }}" class="crm-btn-primary px-4 py-2 text-sm">ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="crm-btn-secondary px-4 py-2 text-sm">پیگیری</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="crm-alert-success mb-4 px-4 py-3 text-sm" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="crm-alert-error mb-4 px-4 py-3 text-sm" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="crm-alert-error mb-4 px-4 py-3 text-sm" role="alert">
                <p class="font-medium">اطلاعات ناقص یا نادرست است.</p>
                <ul class="mt-2 list-disc pe-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="crm-footer-glass mt-8 border-t">
        <div class="mx-auto flex max-w-5xl flex-col gap-2 px-4 py-6 text-sm text-white/80 sm:flex-row sm:justify-between">
            <p>{{ $company['company_name'] }}</p>
            <div class="flex flex-col gap-1">
                @if ($company['contact_phone'])<span>تلفن: {{ $company['contact_phone'] }}</span>@endif
                @if ($company['contact_email'])<span>ایمیل: {{ $company['contact_email'] }}</span>@endif
                @if ($company['contact_address'])<span>{{ $company['contact_address'] }}</span>@endif
            </div>
        </div>
    </footer>
</body>
</html>
