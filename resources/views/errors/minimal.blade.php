<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/vazirmatn/vazirmatn.css') }}">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f5f5f4; color: #1c1917; font-family: Vazirmatn, Tahoma, sans-serif; }
        main { width: min(32rem, calc(100% - 2rem)); background: white; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 2px rgb(0 0 0 / 0.06); }
        p { line-height: 1.9; }
        a { color: #0f766e; }
    </style>
</head>
<body>
<main>
    <p style="font-size: .875rem; color: #78716c;">{{ $code }}</p>
    <h1 style="margin: .25rem 0 0; font-size: 1.5rem;">{{ $title }}</h1>
    <p>{{ $message }}</p>
    <p><a href="{{ url('/') }}">بازگشت به صفحه اصلی</a></p>
</main>
</body>
</html>
