<?php

namespace Tests\Unit;

use App\Sms\SmsIrOtpSender;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsIrOtpSenderTest extends TestCase
{
    public function test_it_sends_otp_via_smsir_verify_endpoint(): void
    {
        Config::set('sms.smsir.api_key', 'test-smsir-key');
        Config::set('sms.smsir.otp_template_id', 235057);
        Config::set('sms.smsir.otp_parameter_name', 'CODE');

        Http::fake([
            SmsIrOtpSender::VERIFY_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 89545112, 'cost' => 1.0],
            ], 200),
        ]);

        app(SmsIrOtpSender::class)->sendOtp('09123334444', '123456');

        Http::assertSent(function ($request): bool {
            return $request->url() === SmsIrOtpSender::VERIFY_URL
                && $request->hasHeader('X-API-KEY', 'test-smsir-key')
                && $request['mobile'] === '9123334444'
                && $request['templateId'] === 235057
                && $request['parameters'][0]['name'] === 'CODE'
                && $request['parameters'][0]['value'] === '123456';
        });
    }

    public function test_it_throws_when_smsir_returns_logical_error(): void
    {
        Config::set('sms.smsir.api_key', 'test-smsir-key');
        Config::set('sms.smsir.otp_template_id', 235057);

        Http::fake([
            SmsIrOtpSender::VERIFY_URL => Http::response([
                'status' => 113,
                'message' => 'قالب یافت نشد',
            ], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sms.ir: قالب یافت نشد');

        app(SmsIrOtpSender::class)->sendOtp('09123334444', '123456');
    }

    public function test_normalize_mobile_strips_iran_prefixes(): void
    {
        $sender = app(SmsIrOtpSender::class);

        $this->assertSame('9123334444', $sender->normalizeMobile('09123334444'));
        $this->assertSame('9123334444', $sender->normalizeMobile('+989123334444'));
        $this->assertSame('9123334444', $sender->normalizeMobile('00989123334444'));
    }
}
