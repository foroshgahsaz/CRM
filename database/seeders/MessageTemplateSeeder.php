<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'event_key' => 'otp',
                'title' => 'رمز یک‌بارمصرف',
                'body' => '{company_name}: رمز تأیید شما {otp_code} است. این رمز را در اختیار دیگران نگذارید.',
                'allowed_variables' => ['company_name', 'otp_code', 'tracking_code'],
            ],
            [
                'event_key' => 'request_submitted',
                'title' => 'ثبت درخواست',
                'body' => '{company_name}: درخواست شما ثبت شد. کد رهگیری: {tracking_code}',
                'allowed_variables' => ['company_name', 'tracking_code'],
            ],
            [
                'event_key' => 'customer_info_requested',
                'title' => 'درخواست مدرک',
                'body' => '{company_name}: برای پرونده {tracking_code} مدرک تکمیلی لازم است. از صفحه پیگیری اقدام کنید.',
                'allowed_variables' => ['company_name', 'tracking_code'],
            ],
            [
                'event_key' => 'request_closed',
                'title' => 'نتیجه رسیدگی',
                'body' => '{company_name}: رسیدگی به پرونده {tracking_code} پایان یافت. وضعیت: {public_status}',
                'allowed_variables' => ['company_name', 'tracking_code', 'public_status'],
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::query()->updateOrCreate(
                ['event_key' => $template['event_key']],
                [...$template, 'channel' => 'sms', 'is_active' => true],
            );
        }
    }
}
