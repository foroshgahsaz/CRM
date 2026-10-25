<?php

namespace Tests\Feature;

use App\Models\RequestType;
use App\Services\CustomerAccessService;
use App\Services\RequestSubmissionService;
use App\Sms\LogSmsGateway;
use Tests\CrmTestCase;

class PublicOtpFlowTest extends CrmTestCase
{
    public function test_submission_page_does_not_depend_on_livewire(): void
    {
        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('action="'.route('requests.otp.send').'"', false)
            ->assertSee('name="mobile"', false)
            ->assertDontSee('wire:submit', false);
    }

    public function test_after_sending_code_phone_form_is_replaced_by_code_step(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09121112233'])
            ->assertRedirect(route('requests.create'));

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('ویرایش شماره')
            ->assertSee('name="code"', false)
            ->assertSee('data-otp-timer', false)
            ->assertDontSee('name="mobile" type="tel"', false);
    }

    public function test_full_submission_otp_flow_reaches_request_form(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09121112244']);

        $this->post(route('requests.otp.verify'), ['code' => $this->latestOtp()])
            ->assertRedirect(route('requests.create'));

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('نام و نام خانوادگی خریدار')
            ->assertSee('09121112244');
    }

    public function test_edit_mobile_returns_to_phone_step(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09121112255']);

        $this->post(route('requests.otp.edit'))->assertRedirect(route('requests.create'));

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('name="mobile"', false)
            ->assertDontSee('name="code"', false);
    }

    public function test_wrong_code_keeps_code_step_with_error(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09121112266']);

        $this->post(route('requests.otp.verify'), ['code' => '000000'])
            ->assertRedirect(route('requests.create'))
            ->assertSessionHas('error');

        $this->get(route('requests.create'))->assertSee('name="code"', false);
    }

    public function test_tracking_flow_shows_case_after_valid_code(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        $request = app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'پیگیری سروری',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'آزمون جریان پیگیری سمت سرور.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09123334455', 'track-server-key');

        LogSmsGateway::$captured = [];
        $this->post(route('track.otp.send'), ['tracking_code' => $request->tracking_code])
            ->assertRedirect(route('track.show'));

        $this->get(route('track.show'))
            ->assertSee('ویرایش کد')
            ->assertSee('name="code"', false);

        $response = $this->post(route('track.otp.verify'), ['code' => $this->latestOtp()]);
        $response->assertRedirect(route('track.show'))->assertCookie(CustomerAccessService::COOKIE);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($item) => $item->getName() === CustomerAccessService::COOKIE);

        $this->disableCookieEncryption()
            ->withCookie(CustomerAccessService::COOKIE, $cookie->getValue())
            ->get(route('track.show'))
            ->assertOk()
            ->assertSee($request->tracking_code)
            ->assertDontSee('name="code"', false);
    }

    public function test_open_request_shows_modal_after_otp_and_blocks_new_form(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        $existing = app(\App\Services\RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مشتری باز',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'درخواست باز برای آزمون محدودیت ثبت مجدد.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09127778899', 'open-key-1');

        $this->post(route('requests.otp.send'), ['mobile' => '09127778899']);
        $this->post(route('requests.otp.verify'), ['code' => $this->latestOtp()])
            ->assertRedirect(route('requests.create'))
            ->assertSessionHas('existing_open_request');

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('درخواست باز دارید')
            ->assertSee($existing->tracking_code)
            ->assertDontSee('ثبت نهایی درخواست');
    }

    public function test_otp_step_primary_button_is_send_code(): void
    {
        $this->post(route('requests.otp.send'), ['mobile' => '09128889900']);

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('ارسال کد')
            ->assertDontSee('دریافت رمز یک‌بارمصرف');
    }

    public function test_home_landing_stores_prefill_and_redirects_to_submission_form(): void
    {
        $type = RequestType::query()->where('is_active', true)->firstOrFail();

        $this->post(route('requests.otp.send'), [
            'source' => 'home',
            'mobile' => '09123334455',
            'request_type_id' => $type->id,
            'full_name' => 'کاربر تست',
            'description' => 'شرح کوتاه از صفحه اصلی',
            'invoice_number' => 'INV-99',
        ])->assertRedirect(route('requests.create'))
            ->assertSessionHas('submission.prefill');

        $this->post(route('requests.otp.verify'), ['code' => $this->latestOtp()])
            ->assertRedirect(route('requests.create'));

        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('name="request_type_id"', false)
            ->assertSee('>'.e($type->name).'</option>', false)
            ->assertSee('کاربر تست')
            ->assertSee('INV-99')
            ->assertSee('شرح کوتاه از صفحه اصلی');
    }

    public function test_home_page_shows_track_and_submit_nav_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('پیگیری درخواست')
            ->assertSee('href="'.route('track.show').'"', false)
            ->assertSee('ثبت درخواست')
            ->assertSee('name="request_type_id"', false);
    }

    private function latestOtp(): string
    {
        $message = LogSmsGateway::$captured[array_key_last(LogSmsGateway::$captured)]['message'] ?? '';
        preg_match('/\d{4,8}/', $message, $matches);
        $this->assertNotEmpty($matches[0] ?? null);

        return $matches[0];
    }
}
