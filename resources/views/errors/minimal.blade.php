<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/vazirmatn/vazirmatn.css') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="crm-page-bg grid min-h-screen place-items-center p-4">
<main class="crm-card w-full max-w-md p-8 text-center">
    <p class="text-sm font-medium text-brand-600">{{ $code }}</p>
    <h1 class="mt-2 text-2xl font-bold text-brand-900">{{ $title }}</h1>
    <p class="mt-4 leading-8 text-slate-600">{{ $message }}</p>
    <a href="{{ url('/') }}" class="crm-btn-solid mt-6 inline-flex">بازگشت به صفحه اصلی</a>
</main>
</body>
</html>
