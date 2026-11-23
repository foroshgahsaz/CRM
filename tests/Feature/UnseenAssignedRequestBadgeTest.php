<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Enums\RequestStatus;
use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\RequestAssignment;
use App\Models\ServiceRequest;
use App\Services\RequestViewService;
use App\Services\UnseenAssignedRequestCounter;
use Tests\CrmTestCase;

class UnseenAssignedRequestBadgeTest extends CrmTestCase
{
    public function test_expert_badge_counts_unopened_assigned_requests_until_opened(): void
    {
        $expert = $this->staff('expert');
        $other = $this->staff('expert');
        $counter = app(UnseenAssignedRequestCounter::class);

        $unseenMine = $this->assignedRequest($expert, viewed: false);
        $this->assignedRequest($expert, viewed: false);
        $this->assignedRequest($expert, viewed: true);
        $this->assignedRequest($other, viewed: false);

        $this->assertSame(2, $counter->countFor($expert));
        $this->assertSame(1, $counter->countFor($other));

        $this->actingAs($expert);
        $this->assertSame('2', ServiceRequestResource::getNavigationBadge());
        $this->assertSame('danger', ServiceRequestResource::getNavigationBadgeColor());

        app(RequestViewService::class)->record($unseenMine->refresh(), $expert);

        $this->assertSame(1, $counter->countFor($expert));
        $this->assertSame('1', ServiceRequestResource::getNavigationBadge());

        app(RequestViewService::class)->record($unseenMine->refresh(), $expert);
        $this->assertSame('1', ServiceRequestResource::getNavigationBadge());
    }

    public function test_closed_request_is_not_counted(): void
    {
        $expert = $this->staff('expert');
        $request = $this->assignedRequest($expert, viewed: false);
        $request->withinWorkflow(function () use ($request): void {
            $request->status = RequestStatus::Closed;
            $request->closed_at = now();
            $request->save();
        });

        $this->assertSame(0, app(UnseenAssignedRequestCounter::class)->countFor($expert));
        $this->actingAs($expert);
        $this->assertNull(ServiceRequestResource::getNavigationBadge());
    }

    private function assignedRequest(\App\Models\User $expert, bool $viewed): ServiceRequest
    {
        $request = ServiceRequest::factory()->create([
            'status' => RequestStatus::Assigned,
            'current_owner_id' => $expert->id,
        ]);

        $assignment = RequestAssignment::query()->create([
            'service_request_id' => $request->id,
            'to_user_id' => $expert->id,
            'assignment_type' => AssignmentType::Initial,
            'status' => AssignmentStatus::Active,
            'active_lock' => $request->id,
            'first_viewed_at' => $viewed ? now() : null,
        ]);

        $request->withinWorkflow(function () use ($request, $assignment): void {
            $request->current_assignment_id = $assignment->id;
            $request->save();
        });

        return $request->refresh();
    }
}
