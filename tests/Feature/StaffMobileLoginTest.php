<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Services\SmsService;
use App\Sms\LogSmsGateway;
use App\Sms\SmsGateway;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\CrmTestCase;

class StaffMobileLoginTest extends CrmTestCase
{
    public function test_staff_can_switch_to_mobile_otp_login_and_authenticate(): void
    {
        Config::set('sms.gateway', 'log');
        Config::set('crm.otp_debug', false);
        $this->app->bind(SmsGateway::class, fn (): LogSmsGateway => new LogSmsGateway);
        $this->app->forgetInstance(SmsService::class);
        LogSmsGateway::$captured = [];

        $user = $this->staff('super_admin', [
            'email' => 'staff-mobile@example.com',
            'mobile' => '09123000001',
        ]);

        $component = Livewire::test(Login::class)
            ->call('switchToMobileLogin')
            ->set('data.mobile', '09123000001')
            ->call('sendStaffLoginOtp');

        $code = $this->extractOtpFromCapturedSms();
        $this->assertNotNull($code);

        $component
            ->set('data.code', $code)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    private function extractOtpFromCapturedSms(): ?string
    {
        $last = LogSmsGateway::$captured !== [] ? LogSmsGateway::$captured[array_key_last(LogSmsGateway::$captured)] : [];
        $message = $last['message'] ?? '';

        if (preg_match('/(\d{4,8})/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
