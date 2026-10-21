<?php

namespace Tests\Unit;

use App\Services\OtpSmsDelivery;
use App\Sms\SmsIrOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpSmsDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_uses_sms_ir_verify_when_gateway_is_sms_ir(): void
    {
        Config::set('sms.gateway', 'sms_ir');
        Config::set('sms.smsir.api_key', 'test-smsir-key');
        Config::set('sms.smsir.otp_template_id', 235057);
        Config::set('sms.smsir.otp_parameter_name', 'CODE');

        Http::fake([
            SmsIrOtpSender::VERIFY_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 1, 'cost' => 1],
            ]),
        ]);

        app(OtpSmsDelivery::class)->send('09121234567', '112233', 'otp-delivery-test');

        Http::assertSent(fn ($request) => $request->url() === SmsIrOtpSender::VERIFY_URL);
    }
}
