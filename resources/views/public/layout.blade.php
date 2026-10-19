<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $company['company_name'] }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/vazirmatn/vazirmatn.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-ink antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @if (! empty($company['logo']))
                    <img src="{{ asset('storage/'.$company['logo']) }}" alt="" class="h-11 w-11 rounded-lg object-cover">
                @else
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-brand-700 text-sm font-bold text-white">درخواست</span>
                @endif
                <span>
                    <span class="block text-base font-bold text-stone-900">{{ $company['company_name'] }}</span>
                    <span class="block text-sm text-stone-500">{{ $company['company_tagline'] }}</span>
                </span>
            </a>
            <nav class="flex gap-2 text-sm">
                <a href="{{ route('requests.create') }}" class="rounded-lg bg-brand-700 px-3 py-2 font-medium text-white hover:bg-brand-800">ثبت درخواست</a>
                <a href="{{ route('track.show') }}" class="rounded-lg border border-stone-300 px-3 py-2 font-medium text-stone-800 hover:bg-stone-50">پیگیری</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
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

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-col gap-2 px-4 py-6 text-sm text-stone-600 sm:flex-row sm:justify-between">
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
