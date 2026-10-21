<?php

namespace Tests\Feature;

use App\Livewire\Public\SubmissionOtpFlow;
use Livewire\Livewire;
use Tests\CrmTestCase;

class SubmissionOtpFlowLivewireTest extends CrmTestCase
{
    public function test_send_otp_hides_phone_form_and_shows_code_step(): void
    {
        Livewire::test(SubmissionOtpFlow::class)
            ->set('mobile', '09121112233')
            ->call('sendOtp')
            ->assertSet('step', 'otp')
            ->assertSee('ویرایش شماره')
            ->assertDontSee('دریافت رمز یک‌بارمصرف');
    }

    public function test_verify_otp_marks_session_verified(): void
    {
        $component = Livewire::test(SubmissionOtpFlow::class)
            ->set('mobile', '09121112233')
            ->call('sendOtp');

        $message = \App\Sms\LogSmsGateway::$captured[array_key_last(\App\Sms\LogSmsGateway::$captured)]['message'] ?? '';
        preg_match('/\d{4,8}/', $message, $matches);
        $code = $matches[0] ?? null;
        $this->assertNotEmpty($code);

        Livewire::test(SubmissionOtpFlow::class)
            ->set('mobile', '09121112233')
            ->set('step', 'otp')
            ->set('otp', $code)
            ->call('verifyOtp')
            ->assertRedirect(route('requests.access.bootstrap'));

        $this->get(route('requests.access.bootstrap'))
            ->assertRedirect(route('requests.create'));

        $this->assertTrue(session('submission.verified') === true);
        $this->get(route('requests.create'))
            ->assertOk()
            ->assertSee('نام و نام خانوادگی خریدار');
    }
}
