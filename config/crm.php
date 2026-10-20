<?php

return [

    'company_name' => 'سامانه پیگیری درخواست',
    'company_tagline' => 'ثبت، پیگیری و رسیدگی به درخواست مشتریان',
    'company_description' => 'درخواست خود را ثبت کنید و با کد رهگیری، وضعیت رسیدگی را به‌صورت امن دنبال کنید.',
    'contact_phone' => '',
    'contact_email' => '',
    'contact_address' => '',
    'required_documents_text' => 'تصویر فاکتور، تصویر کالا و در صورت وجود، تصویر بسته‌بندی را بارگذاری کنید. فایل‌های مجاز: JPG، PNG، WEBP و PDF.',
    'process_guide' => 'پس از تأیید شماره موبایل، فرم درخواست را تکمیل کنید. کد رهگیری را نگه دارید. برای مشاهده وضعیت، همان کد و رمز یک‌بارمصرف لازم است.',

    'sms_enabled' => false,
    'otp_ttl_seconds' => 120,
    'otp_max_attempts' => 5,
    'otp_resend_seconds' => 60,
    'otp_length' => 6,
    'customer_session_ttl_minutes' => 30,
    'max_upload_files' => 5,
    'max_upload_kb' => 5120,
    'sla_enabled' => true,
    'sla_warning_hours' => 8,
    'business_days' => ['sat', 'sun', 'mon', 'tue', 'wed'],
    'business_start' => '08:00',
    'business_end' => '17:00',
    'holidays' => [],

    // فقط برای تست بدون SMS.ir — OTP در storage/logs/laravel.log نوشته می‌شود. در go-live خاموش کنید.
    'otp_debug' => (bool) env('CRM_OTP_DEBUG', false),

];
