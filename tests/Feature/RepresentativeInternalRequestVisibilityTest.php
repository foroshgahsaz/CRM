<?php

namespace Tests\Feature;

use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestSubmissionService;
use Illuminate\Support\Facades\Gate;
use Tests\CrmTestCase;

class RepresentativeInternalRequestVisibilityTest extends CrmTestCase
{
    public function test_representative_sees_internally_submitted_request_in_list_scope(): void
    {
        $representative = $this->staff('representative');

        $request = app(RequestSubmissionService::class)->submitInternal($representative, [
            'full_name' => 'مشتری تست',
            'mobile' => '09121111111',
            'request_type_id' => RequestType::query()->firstOrFail()->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'شرح درخواست',
            'priority' => 'normal',
            'fields' => [],
        ], []);

        $this->assertSame($representative->id, $request->internal_submitted_by_id);
        $this->assertTrue(Gate::forUser($representative)->allows('view', $request));

        $visibleIds = ServiceRequest::query()->visibleTo($representative)->pluck('id');

        $this->assertTrue($visibleIds->contains($request->id));
    }

    public function test_representative_role_includes_create_internal_permission(): void
    {
        $representative = User::factory()->create();
        $representative->assignRole('representative');

        $this->assertTrue($representative->can('requests.create_internal'));
    }
}