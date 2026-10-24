<?php

namespace Tests\Feature;

use App\Enums\SmsStatus;
use App\Exceptions\RetryableSmsException;
use App\Filament\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Models\RequestType;
use App\Models\SmsLog;
use App\Services\ReportService;
use App\Services\RequestSubmissionService;
use App\Services\OtpSmsDelivery;
use App\Services\SettingsService;
use App\Services\SmsService;
use App\Sms\SmsGateway;
use App\Sms\SmsResult;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\CrmTestCase;

class SmsReportAndPanelTest extends CrmTestCase
{
    public function test_customer_sms_is_skipped_when_disabled_and_delivery_is_idempotent(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        $request = app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مشتری پیامک',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'شرح کافی برای آزمون پیامک و گزارش.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09124000000', 'sms-key');

        $this->assertDatabaseHas('sms_logs', [
            'event_key' => 'request_submitted',
            'service_request_id' => $request->id,
            'status' => SmsStatus::Skipped->value,
        ]);

        $otpSms = app(OtpSmsDelivery::class);
        $otpSms->send('09124000000', '123456', 'otp-once');
        $otpSms->send('09124000000', '123456', 'otp-once');

        $this->assertSame(1, SmsLog::query()->where('idempotency_key', 'otp-once')->count());
    }

    public function test_retryable_sms_failure_is_reported_without_sending_forever(): void
    {
        $this->app->bind(SmsGateway::class, fn (): SmsGateway => new class implements SmsGateway
        {
            public int $calls = 0;

            public function send(string $mobile, string $message): SmsResult
            {
                $this->calls++;

                return SmsResult::failed('timeout', 'gateway timeout', true);
            }

            public function name(): string
            {
                return 'fake';
            }
        });
        $this->app->forgetInstance(SmsService::class);
        app(SettingsService::class)->set('sms_enabled', true, 'sms', false);

        $this->expectException(RetryableSmsException::class);
        app(SmsService::class)->deliver('09124000001', 'request_submitted', [
            'company_name' => 'آزمون',
            'tracking_code' => 'RQ-TEST01',
        ], null, 'sms-retry');
    }

    public function test_report_uses_real_requests_and_defines_handling_time(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مشتری گزارش',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'شرح کافی برای محاسبه گزارش مدیریتی.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09124000002', 'report-key');

        $summary = app(ReportService::class)->summary(now()->subDays(7), now()->addMinute(), $type->id);

        $this->assertSame(1, $summary['submitted_count']);
        $this->assertSame(1, $summary['unassigned_count']);
        $this->assertArrayHasKey('avg_handling_hours', $summary);
    }

    public function test_panel_actions_follow_permissions(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        $request = app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مشتری پنل',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'شرح کافی برای آزمون دکمه‌های پنل.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09124000003', 'panel-key');

        $this->actingAs($admin);
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->assertActionVisible('assign')
            ->assertActionHidden('approve')
            ->assertActionHidden('complete');

        $this->actingAs($expert);
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->assertNotFound();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($this->staff('auditor'))->get('/admin/reports')->assertOk();
        $this->actingAs($expert)->get('/admin/manage-settings')->assertForbidden();
    }
}
