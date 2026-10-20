<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\CrmTestCase;

class SessionCookieConfigurationTest extends CrmTestCase
{
    public function test_otp_post_keeps_session_when_secure_cookie_misconfigured_for_http(): void
    {
        Config::set('session.secure', true);
        Config::set('crm.session_secure_auto', true);

        $response = $this->post(route('requests.otp.send'), ['mobile' => '09121111111']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertNotSame('', session()->getId());
    }
}
