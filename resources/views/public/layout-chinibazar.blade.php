<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | '.$company['company_name'] : $company['company_name'].' | خدمات پس از فروش' }}</title>
    <meta name="description" content="{{ $metaDescription ?? $company['company_tagline'] }}">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    @include('partials.jalali-datepicker-assets')
    @vite(['resources/css/chinibazar-premium.css', 'resources/js/app.js'])
</head>
<body>
@yield('content')
</body>
</html>
