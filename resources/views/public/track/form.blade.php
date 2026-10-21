@extends('public.layout')

@section('content')
    @if ($case)
        @include('public.track.case', ['case' => $case])
    @else
        <div class="crm-card crm-card-glow mx-auto grid max-w-xl gap-6 p-6 sm:p-9">
            <div>
                <h1 class="crm-page-title">پیگیری درخواست</h1>
                <p class="crm-page-lead">کد رهگیری را وارد کنید. رمز یک‌بارمصرف فقط به شماره ثبت‌شده در همان پرونده ارسال می‌شود.</p>
            </div>
            <livewire:public.track-otp-flow />
        </div>
    @endif
@endsection
