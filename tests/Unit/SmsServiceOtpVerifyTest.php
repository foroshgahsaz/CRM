<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceOtpVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_uses_sms_ir_verify_when_template_id_is_set(): void
    {
        $this->seed();

        Config::set('sms.gateway', 'sms_ir');
        Config::set('sms.smsir.api_key', 'test-api-key');
        Config::set('sms.smsir.line_number', '30004505000017');
        Config::set('sms.smsir.otp_template_id', 235057);

        Http::fake([
            'api.sms.ir/v1/send/verify' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 1, 'cost' => 1],
            ]),
        ]);

        $sent = app(SmsService::class)->deliver('09121234567', 'otp', [
            'otp_code' => '112233',
            'company_name' => 'هومن سرویس',
            'tracking_code' => '',
        ], null, 'otp-verify-test');

        $this->assertTrue($sent);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.sms.ir/v1/send/verify');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/send/bulk'));
    }

    public function test_otp_falls_back_to_bulk_when_template_id_missing(): void
    {
        $this->seed();

        Config::set('sms.gateway', 'sms_ir');
        Config::set('sms.smsir.api_key', 'test-api-key');
        Config::set('sms.smsir.line_number', '30004505000017');
        Config::set('sms.smsir.otp_template_id', null);

        Http::fake([
            'api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['packId' => 'p1', 'messageIds' => [1], 'cost' => 1],
            ]),
        ]);

        app(SmsService::class)->deliver('09121234567', 'otp', [
            'otp_code' => '445566',
            'company_name' => 'CRM',
            'tracking_code' => '',
        ], null, 'otp-bulk-test');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.sms.ir/v1/send/bulk');
    }
}
