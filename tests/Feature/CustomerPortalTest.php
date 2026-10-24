<?php

namespace Tests\Feature;

use App\Enums\OtpPurpose;
use App\Models\OtpChallenge;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\SmsLog;
use App\Services\CustomerAccessService;
use App\Sms\LogSmsGateway;
use Illuminate\Http\UploadedFile;
use Tests\CrmTestCase;

class CustomerPortalTest extends CrmTestCase
{
    public function test_customer_can_verify_mobile_and_submit_a_request_once(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        [$year, $month, $day] = [1404, 6, 15];

        $this->post(route('requests.otp.send'), ['mobile' => '09121111111'])->assertRedirect();
        $code = $this->latestOtp();

        $this->post(route('requests.otp.verify'), ['code' => $code])->assertRedirect(route('requests.create'));
        $key = session('submission.key');
        $this->assertNotEmpty($key);

        $payload = [
            'full_name' => 'نسترن کریمی',
            'request_type_id' => $type->id,
            'purchase_jalali' => "{$year}/{$month}/{$day}",
            'description' => 'کالای دریافتی شکسته است و نیاز به رسیدگی دارد.',
            'submission_key' => $key,
        ];

        $payload['attachments'] = [UploadedFile::fake()->image('invoice.jpg')];

        $this->post(route('requests.store'), $payload)->assertRedirect(route('requests.success'));
        $this->post(route('requests.store'), $payload)->assertSessionHasErrors('submission_key');

        $this->assertSame(1, ServiceRequest::query()->count());
        $request = ServiceRequest::query()->firstOrFail();
        $this->assertNotNull($request->purchase_date);
        $this->assertMatchesRegularExpression('/^RQ-[A-Z0-9]{10}$/', $request->tracking_code);

        $this->get(route('requests.success'))->assertOk()->assertSee($request->tracking_code);
    }

    public function test_incomplete_submission_is_rejected(): void
    {
        $this->verifyMobile('09122222222');

        $this->post(route('requests.store'), [
            'submission_key' => session('submission.key'),
        ])->assertSessionHasErrors(['full_name', 'request_type_id', 'description']);

        $this->assertSame(0, ServiceRequest::query()->count());
    }

    public function test_inactive_request_type_cannot_be_submitted(): void
    {
        $type = RequestType::query()->where('slug', 'shortage')->firstOrFail();
        $type->update(['is_active' => false]);
        [$year, $month, $day] = [1404, 6, 15];
        $this->verifyMobile('09123333333');

        $this->post(route('requests.store'), [
            'full_name' => 'مشتری آزمایشی',
            'request_type_id' => $type->id,
            'purchase_jalali' => "{$year}/{$month}/{$day}",
            'description' => 'شرح کافی برای ثبت درخواست آزمایشی.',
            'submission_key' => session('submission.key'),
        ])->assertSessionHasErrors('request_type_id');
    }

    public function test_invalid_attachment_is_rejected(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        [$year, $month, $day] = [1404, 6, 15];
        $this->verifyMobile('09124444444');

        $this->post(route('requests.store'), [
            'full_name' => 'مشتری آزمایشی',
            'request_type_id' => $type->id,
            'purchase_jalali' => "{$year}/{$month}/{$day}",
            'description' => 'شرح کافی برای ثبت درخواست آزمایشی.',
            'submission_key' => session('submission.key'),
            'attachments' => [UploadedFile::fake()->create('shell.php', 20, 'application/x-php')],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, ServiceRequest::query()->count());
    }

    public function test_otp_rejects_wrong_expired_reused_and_excessive_attempts(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09125555555'])->assertRedirect();
        $code = $this->latestOtp();

        $this->post(route('requests.otp.verify'), ['code' => '000000'])->assertSessionHas('error');
        $this->assertNull(session('submission.verified'));

        $this->travel(3)->minutes();
        $this->post(route('requests.otp.verify'), ['code' => $code])->assertSessionHas('error');

        $this->travelBack();
        LogSmsGateway::$captured = [];
        $this->post(route('requests.otp.send'), ['mobile' => '09126666666'])->assertRedirect();
        $fresh = $this->latestOtp();
        $this->post(route('requests.otp.verify'), ['code' => $fresh])->assertRedirect();
        $this->post(route('requests.otp.verify'), ['code' => $fresh])->assertSessionHas('error');

        LogSmsGateway::$captured = [];
        $this->post(route('requests.otp.send'), ['mobile' => '09127777777'])->assertRedirect();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('requests.otp.verify'), ['code' => '111111']);
        }

        $this->assertSame(5, OtpChallenge::query()->where('mobile', '09127777777')->first()->attempts);
        $this->post(route('requests.otp.verify'), ['code' => $this->latestOtp()])->assertSessionHas('error');
    }

    public function test_otp_resend_is_throttled_and_the_raw_code_is_not_stored_or_logged(): void
    {
        config(['sms.log_codes' => false]);

        $this->post(route('requests.otp.send'), ['mobile' => '09128888888'])->assertRedirect();
        $code = $this->latestOtp();
        $this->post(route('requests.otp.send'), ['mobile' => '09128888888'])->assertRedirect()->assertSessionHas('error');

        $this->travel(61)->seconds();
        $this->post(route('requests.otp.send'), ['mobile' => '09128888888'])->assertRedirect()->assertSessionHas('status');
        $this->assertSame(2, OtpChallenge::query()->where('mobile', '09128888888')->count());

        $challenge = OtpChallenge::query()->where('mobile', '09128888888')->latest('id')->firstOrFail();
        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertStringNotContainsString($code, json_encode($challenge->only([
            'mobile', 'purpose', 'tracking_code', 'attempts', 'ip_address',
        ]), JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString($code, json_encode(SmsLog::query()->get()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_tracking_does_not_reveal_unknown_codes_and_limits_requests(): void
    {
        $this->post(route('track.otp.send'), ['tracking_code' => 'RQ-UNKNOWN99'])->assertRedirect()->assertSessionHas('status');
        $this->assertSame(0, OtpChallenge::query()->where('purpose', OtpPurpose::Track)->count());
        $this->assertNull(session('tracking.pending_code'));

        foreach (range(1, 3) as $ignored) {
            $this->post(route('requests.otp.send'), ['mobile' => '09129999999']);
        }

        $this->post(route('requests.otp.send'), ['mobile' => '09129999999'])->assertStatus(429);
    }

    public function test_customer_session_cannot_open_another_request(): void
    {
        $first = $this->submit('09120001111', 'کلید اول برای جداسازی پرونده‌ها');
        $second = $this->submit('09120002222', 'کلید دوم برای جداسازی پرونده‌ها');
        $this->openTracking($first);

        $this->get(route('track.show'))
            ->assertOk()
            ->assertSee($first->tracking_code)
            ->assertDontSee($second->tracking_code);

        $this->post(route('track.logout'))->assertRedirect(route('track.show'));
        $this->get(route('track.show'))->assertDontSee($first->tracking_code);
    }

    private function verifyMobile(string $mobile): void
    {
        LogSmsGateway::$captured = [];
        $this->post(route('requests.otp.send'), ['mobile' => $mobile])->assertRedirect();
        $this->post(route('requests.otp.verify'), ['code' => $this->latestOtp()])->assertRedirect();
    }

    private function submit(string $mobile, string $description): ServiceRequest
    {
        $type = RequestType::query()->where('slug', 'mismatch')->firstOrFail();
        [$year, $month, $day] = [1404, 6, 15];
        $this->verifyMobile($mobile);

        $this->post(route('requests.store'), [
            'full_name' => 'مشتری پیگیری',
            'request_type_id' => $type->id,
            'purchase_jalali' => "{$year}/{$month}/{$day}",
            'description' => $description,
            'submission_key' => session('submission.key'),
            'attachments' => [UploadedFile::fake()->image('proof.jpg')],
        ])->assertRedirect(route('requests.success'));

        return ServiceRequest::query()->where('description', $description)->firstOrFail();
    }

    private function openTracking(ServiceRequest $request): void
    {
        LogSmsGateway::$captured = [];
        $this->post(route('track.otp.send'), ['tracking_code' => $request->tracking_code])->assertRedirect();
        $response = $this->post(route('track.otp.verify'), ['code' => $this->latestOtp()]);
        $response->assertRedirect(route('track.show'))->assertCookie(CustomerAccessService::COOKIE);
        $cookie = collect($response->headers->getCookies())->first(
            fn ($item) => $item->getName() === CustomerAccessService::COOKIE,
        );
        $this->disableCookieEncryption()->withCookie(CustomerAccessService::COOKIE, $cookie->getValue());
    }

    private function latestOtp(): string
    {
        $message = LogSmsGateway::$captured[array_key_last(LogSmsGateway::$captured)]['message'] ?? '';
        preg_match('/\d{4,8}/', $message, $matches);
        $this->assertNotEmpty($matches[0] ?? null);

        return $matches[0];
    }
}
