<?php

namespace Tests\Unit;

use App\Sms\SmsIrGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsIrGatewayTest extends TestCase
{
    public function test_sends_bulk_request_with_api_key_header(): void
    {
        Config::set('sms.smsir.api_key', 'test-api-key');
        Config::set('sms.smsir.line_number', '30004505000017');
        Config::set('sms.http.timeout', 10);

        Http::fake([
            SmsIrGateway::BULK_URL => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => [
                    'packId' => 'pack-uuid',
                    'messageIds' => [86522023],
                    'cost' => 1.0,
                ],
            ]),
        ]);

        $result = (new SmsIrGateway)->send('09121234567', 'کد: 123456');

        $this->assertTrue($result->successful);
        $this->assertSame('pack-uuid', $result->providerReference);

        Http::assertSent(function ($request): bool {
            return $request->url() === SmsIrGateway::BULK_URL
                && $request->hasHeader('X-API-KEY', 'test-api-key')
                && $request['lineNumber'] === 30004505000017
                && $request['messageText'] === 'کد: 123456'
                && $request['mobiles'] === ['09121234567'];
        });
    }

    public function test_reports_provider_error_message(): void
    {
        Config::set('sms.smsir.api_key', 'bad-key');
        Config::set('sms.smsir.line_number', '30004505000017');

        Http::fake([
            SmsIrGateway::BULK_URL => Http::response([
                'status' => 10,
                'message' => 'کلید وب سرویس نامعتبر است',
                'data' => null,
            ], 401),
        ]);

        $result = (new SmsIrGateway)->send('09121234567', 'test');

        $this->assertFalse($result->successful);
        $this->assertSame('کلید وب سرویس نامعتبر است', $result->errorMessage);
    }

}
