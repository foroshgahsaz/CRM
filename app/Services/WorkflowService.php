<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;

class WorkflowService
{
    public function transition(ServiceRequest $request, RequestStatus $to, ?User $actor, ?string $reason = null, array $metadata = []): void
    {
        $from = $request->status;

        if ($from === $to) {
            return;
        }

        if (! $from->canTransitionTo($to)) {
            throw new WorkflowException('این درخواست در وضعیت فعلی قابل این تغییر نیست.');
        }

        $request->withinWorkflow(function () use ($request, $to): void {
            $request->status = $to;
            $request->save();
        });

        RequestStatusHistory::query()->create([
            'service_request_id' => $request->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'changed_by' => $actor?->id,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : app(AuditLogService::class)->sanitize($metadata),
            'created_at' => now(),
        ]);
    }
}
