<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Services\SettingsService;
use App\Services\SmsService;
use App\Support\SmsIrPattern;
use Illuminate\Support\Facades\Http;
use Tests\CrmTestCase;

class SmsIrPatternTest extends CrmTestCase
{
    public function test_verify_api_is_used_when_template_id_is_set(): void
    {
        config(['sms.gateway' => 'sms_ir', 'sms.smsir.api_key' => 'test-key']);

        MessageTemplate::query()->where('event_key', 'request_submitted')->update([
            'sms_ir_template_id' => 235057,
            'sms_ir_parameter_map' => ['company_name' => 'BRAND', 'tracking_code' => 'CODE'],
        ]);

        Http::fake([
            'https://api.sms.ir/v1/send/verify' => Http::response(['status' => 1, 'data' => ['messageId' => 99]], 200),
        ]);

        app(SettingsService::class)->set('sms_enabled', true, 'sms', false);

        $sent = app(SmsService::class)->deliver('09121234567', 'request_submitted', [
            'company_name' => 'آزمون',
            'tracking_code' => 'RQ-TEST',
        ], null, 'pattern-test-'.uniqid());

        $this->assertTrue($sent);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.sms.ir/v1/send/verify'
            && $request['templateId'] === 235057
            && collect($request['parameters'])->contains(fn ($p) => $p['name'] === 'CODE' && $p['value'] === 'RQ-TEST'));
    }

    public function test_default_parameter_map_for_otp(): void
    {
        $map = SmsIrPattern::defaultMap('otp', ['otp_code', 'company_name']);

        $this->assertSame('CODE', $map['otp_code']);
    }
}
