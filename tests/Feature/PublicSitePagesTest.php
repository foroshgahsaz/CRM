<?php

namespace Tests\Feature;

use Tests\CrmTestCase;

class PublicSitePagesTest extends CrmTestCase
{
    public function test_public_compliance_pages_are_accessible(): void
    {
        $this->get(route('site.about'))->assertOk()->assertSee('درباره');
        $this->get(route('site.contact'))->assertOk()->assertSee('تماس');
        $this->get(route('site.privacy'))->assertOk()->assertSee('OTP');
    }
}
