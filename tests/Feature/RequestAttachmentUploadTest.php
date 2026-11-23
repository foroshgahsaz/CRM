<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Gate;
use Tests\CrmTestCase;

class RequestAttachmentUploadTest extends CrmTestCase
{

    public function test_representative_can_add_customer_attachment_on_viewable_request(): void
    {
        $representative = $this->staff('representative');
        $request = ServiceRequest::factory()->create([
            'internal_submitted_by_id' => $representative->id,
        ]);

        $this->assertTrue(Gate::forUser($representative)->allows('addAttachment', $request));
    }

    public function test_unrelated_user_cannot_add_attachment(): void
    {
        $expert = $this->staff('expert');
        $request = ServiceRequest::factory()->create();

        $this->assertFalse(Gate::forUser($expert)->allows('addAttachment', $request));
    }
}