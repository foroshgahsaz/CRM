@extends('public.layout')

@section('content')
    <article class="crm-card mx-auto max-w-3xl p-6 sm:p-9">
        <h1 class="crm-page-title">تماس با ما</h1>
        <p class="crm-page-lead">راه‌های ارتباط با {{ $company['company_name'] }}</p>

        <dl class="mt-8 grid gap-4 text-sm">
            <div class="crm-form-section grid gap-1">
                <dt class="font-bold text-brand-900">نام مجموعه</dt>
                <dd class="text-slate-700">{{ $company['company_name'] }}</dd>
            </div>
            @if ($company['contact_phone'])
                <div class="crm-form-section grid gap-1">
                    <dt class="font-bold text-brand-900">تلفن</dt>
                    <dd class="text-slate-700" dir="ltr">{{ $company['contact_phone'] }}</dd>
                </div>
            @endif
            @if ($company['contact_email'])
                <div class="crm-form-section grid gap-1">
                    <dt class="font-bold text-brand-900">ایمیل</dt>
                    <dd class="text-slate-700" dir="ltr">{{ $company['contact_email'] }}</dd>
                </div>
            @endif
            @if ($company['contact_address'])
                <div class="crm-form-section grid gap-1">
                    <dt class="font-bold text-brand-900">آدرس</dt>
                    <dd class="text-slate-700">{{ $company['contact_address'] }}</dd>
                </div>
            @endif
        </dl>

        @if (! $company['contact_phone'] && ! $company['contact_email'] && ! $company['contact_address'])
            <p class="crm-alert-error mt-6 text-sm">
                اطلاعات تماس هنوز در پنل مدیریت (تنظیمات → عمومی) ثبت نشده است. برای تأیید الگوی SMS.ir حتماً تلفن و آدرس را وارد کنید.
            </p>
        @endif

        <p class="mt-6 text-sm text-slate-600">
            پشتیبانی درخواست‌ها از طریق
            <a href="{{ route('requests.create') }}" class="font-semibold text-brand-700">ثبت درخواست</a>
            انجام می‌شود.
        </p>
    </article>
@endsection
