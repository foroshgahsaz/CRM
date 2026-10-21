<?php

namespace Tests\Feature;

use App\Livewire\Public\TrackOtpFlow;
use App\Models\RequestType;
use App\Services\CustomerAccessService;
use App\Services\RequestSubmissionService;
use App\Sms\LogSmsGateway;
use Livewire\Livewire;
use Tests\CrmTestCase;

class TrackOtpFlowLivewireTest extends CrmTestCase
{
    public function test_verify_otp_redirects_with_customer_access_cookie(): void
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();
        $request = app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'پیگیری Livewire',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'آزمون پیگیری با Livewire OTP.',
            'fields' => [],
        ], [], '09123334455', 'track-lw-key');

        LogSmsGateway::$captured = [];

        Livewire::test(TrackOtpFlow::class)
            ->set('trackingCode', $request->tracking_code)
            ->call('sendOtp')
            ->assertSet('step', 'otp');

        $message = LogSmsGateway::$captured[array_key_last(LogSmsGateway::$captured)]['message'] ?? '';
        preg_match('/\d{4,8}/', $message, $matches);
        $code = $matches[0] ?? null;
        $this->assertNotEmpty($code);

        Livewire::test(TrackOtpFlow::class)
            ->set('trackingCode', $request->tracking_code)
            ->set('step', 'otp')
            ->set('otp', $code)
            ->call('verifyOtp')
            ->assertRedirect(route('track.access.bootstrap'));

        $bootstrap = $this->get(route('track.access.bootstrap'));
        $bootstrap->assertRedirect(route('track.show'));
        $cookie = collect($bootstrap->headers->getCookies())->first(
            fn ($item) => $item->getName() === CustomerAccessService::COOKIE,
        );
        $this->assertNotNull($cookie);

        $this->disableCookieEncryption()
            ->withCookie(CustomerAccessService::COOKIE, $cookie->getValue())
            ->get(route('track.show'))
            ->assertOk()
            ->assertSee($request->tracking_code);
    }
}
