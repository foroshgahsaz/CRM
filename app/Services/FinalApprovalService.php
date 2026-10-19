<?php

namespace App\Services;

use App\Enums\ApprovalDecisionType;
use App\Enums\MessageType;
use App\Enums\MessageVisibility;
use App\Enums\RequestStatus;
use App\Events\ServiceRequestApproved;
use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestRejected;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalDecision;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FinalApprovalService
{
    public function __construct(
        private WorkflowService $workflow,
        private AssignmentService $assignments,
        private AuditLogService $audit,
    ) {}

    public function approve(ServiceRequest $request, User $actor, string $customerSummary, bool $close): ServiceRequest
    {
        Gate::forUser($actor)->authorize('approve', $request);

        if ($close) {
            Gate::forUser($actor)->authorize('close', $request);
        }

        return DB::transaction(function () use ($request, $actor, $customerSummary, $close): ServiceRequest {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RequestStatus::PendingFinalApproval) {
                throw new WorkflowException('این درخواست در وضعیت فعلی قابل تأیید نهایی نیست.');
            }

            $summary = trim($customerSummary);

            if ($summary === '') {
                throw new WorkflowException('نتیجه قابل نمایش به مشتری الزامی است.');
            }

            $locked->withinWorkflow(function () use ($locked, $actor, $summary): void {
                $locked->approved_by = $actor->id;
                $locked->approved_at = now();
                $locked->customer_visible_summary = $summary;
                $locked->save();
            });

            ApprovalDecision::query()->create([
                'service_request_id' => $locked->id,
                'decision' => ApprovalDecisionType::Approved,
                'decided_by' => $actor->id,
                'reason' => $summary,
            ]);

            RequestMessage::query()->create([
                'service_request_id' => $locked->id,
                'sender_id' => $actor->id,
                'message_type' => MessageType::CustomerVisible,
                'visibility' => MessageVisibility::CustomerVisible,
                'body' => $summary,
                'is_final' => true,
            ]);

            $this->workflow->transition($locked, RequestStatus::Approved, $actor, 'تأیید نهایی');
            $this->audit->record('request.approved', $locked, 'success', [], $actor);
            event(new ServiceRequestApproved($locked->id));

            if ($close) {
                $this->closeLocked($locked, $actor);
            }

            return $locked->refresh();
        });
    }

    public function close(ServiceRequest $request, User $actor): ServiceRequest
    {
        Gate::forUser($actor)->authorize('close', $request);

        return DB::transaction(function () use ($request, $actor): ServiceRequest {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RequestStatus::Approved) {
                throw new WorkflowException('فقط درخواست تأییدشده قابل بستن است.');
            }

            $this->closeLocked($locked, $actor);

            return $locked->refresh();
        });
    }

    public function reject(ServiceRequest $request, User $actor, string $reason, int $assigneeId): ServiceRequest
    {
        Gate::forUser($actor)->authorize('rejectApproval', $request);

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new WorkflowException('دلیل بازگشت پرونده برای اصلاح الزامی است.');
        }

        return DB::transaction(function () use ($request, $actor, $reason, $assigneeId): ServiceRequest {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RequestStatus::PendingFinalApproval) {
                throw new WorkflowException('این درخواست در وضعیت فعلی قابل رد تأیید نیست.');
            }

            $assignment = $this->assignments->openCorrection($locked, $actor, $assigneeId, $reason);

            ApprovalDecision::query()->create([
                'service_request_id' => $locked->id,
                'decision' => ApprovalDecisionType::Rejected,
                'decided_by' => $actor->id,
                'reason' => $reason,
                'assignment_id' => $assignment->id,
            ]);

            $this->audit->record('request.approval_rejected', $locked, 'success', [
                'assignment_id' => $assignment->id,
            ], $actor);
            event(new ServiceRequestRejected($locked->id, $assignment->id));

            return $locked->refresh();
        });
    }

    public function archive(ServiceRequest $request, User $actor): ServiceRequest
    {
        Gate::forUser($actor)->authorize('archive', $request);

        return DB::transaction(function () use ($request, $actor): ServiceRequest {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $locked->withinWorkflow(function () use ($locked): void {
                $locked->archived_at = now();
                $locked->save();
            });
            $this->workflow->transition($locked, RequestStatus::Archived, $actor, 'بایگانی پرونده');
            $this->audit->record('request.archived', $locked, 'success', [], $actor);

            return $locked->refresh();
        });
    }

    public function reopen(ServiceRequest $request, User $actor, string $reason, int $assigneeId): ServiceRequest
    {
        Gate::forUser($actor)->authorize('reopen', $request);

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new WorkflowException('دلیل بازگشایی پرونده الزامی است.');
        }

        return DB::transaction(function () use ($request, $actor, $reason, $assigneeId): ServiceRequest {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $this->workflow->transition($locked, RequestStatus::Reopened, $actor, $reason);
            $assignment = $this->assignments->openReopened($locked, $actor, $assigneeId, $reason);
            $this->audit->record('request.reopened', $locked, 'success', [
                'assignment_id' => $assignment->id,
            ], $actor);

            return $locked->refresh();
        });
    }

    private function closeLocked(ServiceRequest $locked, User $actor): void
    {
        $locked->withinWorkflow(function () use ($locked): void {
            $locked->closed_at = now();
            $locked->save();
        });
        $this->workflow->transition($locked, RequestStatus::Closed, $actor, 'بستن پرونده');
        $this->audit->record('request.closed', $locked, 'success', [], $actor);
        event(new ServiceRequestClosed($locked->id));
    }
}
