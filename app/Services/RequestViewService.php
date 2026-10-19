<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Events\AssignmentFirstViewed;
use App\Models\RequestView;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestViewService
{
    public function __construct(private WorkflowService $workflow) {}

    public function record(ServiceRequest $request, User $viewer): void
    {
        DB::transaction(function () use ($request, $viewer): void {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $assignment = $locked->currentAssignment;

            $isAssignee = $assignment
                && $assignment->isActive()
                && $assignment->to_user_id === $viewer->id;

            if ($isAssignee && $assignment->first_viewed_at === null) {
                $locked->withinWorkflow(function () use ($assignment): void {
                    $assignment->first_viewed_at = now();
                    $assignment->accepted_at = now();
                    $assignment->work_started_at = now();
                    $assignment->save();
                });

                if ($locked->status === RequestStatus::Assigned) {
                    $this->workflow->transition($locked, RequestStatus::InProgress, $viewer, 'شروع رسیدگی توسط مسئول');
                }

                event(new AssignmentFirstViewed($locked->id, $assignment->id, $viewer->id));
            }

            $view = RequestView::query()->firstOrNew([
                'service_request_id' => $locked->id,
                'assignment_id' => $isAssignee ? $assignment->id : 0,
                'user_id' => $viewer->id,
            ]);

            if (! $view->exists) {
                $view->kind = $isAssignee ? 'assignee' : 'staff';
                $view->first_viewed_at = now();
                $view->view_count = 0;
            }

            $view->last_viewed_at = now();
            $view->view_count++;
            $view->save();
        });
    }
}
