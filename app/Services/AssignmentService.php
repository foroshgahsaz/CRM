<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Enums\RequestStatus;
use App\Events\AssignmentCompleted;
use App\Events\ServiceRequestAssigned;
use App\Exceptions\WorkflowException;
use App\Models\OrganizationalUnit;
use App\Models\RequestAssignment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignmentService
{
    public function __construct(
        private WorkflowService $workflow,
        private AuditLogService $audit,
    ) {}

    public function assignInitial(
        ServiceRequest $request,
        User $actor,
        ?int $toUserId,
        ?int $toUnitId,
        ?string $instructions,
        ?string $reason,
    ): RequestAssignment {
        Gate::forUser($actor)->authorize('assign', $request);

        return $this->transfer(
            $request,
            $actor,
            $toUserId,
            $toUnitId,
            $toUserId ? AssignmentType::Initial : AssignmentType::UnitTransfer,
            $reason,
            $instructions,
        );
    }

    public function reassign(
        ServiceRequest $request,
        User $actor,
        ?int $toUserId,
        ?int $toUnitId,
        string $reason,
        ?string $instructions,
    ): RequestAssignment {
        Gate::forUser($actor)->authorize('reassign', $request);

        $type = $toUserId === null ? AssignmentType::UnitTransfer : AssignmentType::Reassignment;

        return $this->transfer($request, $actor, $toUserId, $toUnitId, $type, $reason, $instructions);
    }

    public function claim(ServiceRequest $request, User $actor): RequestAssignment
    {
        Gate::forUser($actor)->authorize('claim', $request);

        return $this->transfer(
            $request,
            $actor,
            $actor->id,
            $actor->organizational_unit_id,
            AssignmentType::Claim,
            'پذیرش درخواست در واحد',
            null,
        );
    }

    public function openCorrection(
        ServiceRequest $request,
        User $actor,
        int $toUserId,
        string $reason,
    ): RequestAssignment {
        return $this->transfer(
            $request,
            $actor,
            $toUserId,
            null,
            AssignmentType::Correction,
            $reason,
            $reason,
        );
    }

    public function openReopened(
        ServiceRequest $request,
        User $actor,
        int $toUserId,
        string $reason,
    ): RequestAssignment {
        return $this->transfer(
            $request,
            $actor,
            $toUserId,
            null,
            AssignmentType::Reopen,
            $reason,
            null,
        );
    }

    public function complete(ServiceRequest $request, User $actor, string $summary): void
    {
        Gate::forUser($actor)->authorize('complete', $request);

        DB::transaction(function () use ($request, $actor, $summary): void {
            $locked = $this->lock($request);
            $assignment = $this->activeAssignment($locked);

            if ($assignment->to_user_id !== $actor->id || $locked->status !== RequestStatus::InProgress) {
                throw new WorkflowException('تکمیل کارشناسی فقط برای مسئول فعال در حال رسیدگی ممکن است.');
            }

            $locked->withinWorkflow(function () use ($assignment, $locked, $summary): void {
                $assignment->status = AssignmentStatus::Completed;
                $assignment->active_lock = null;
                $assignment->completed_at = now();
                $assignment->completion_summary = $summary;
                $assignment->save();

                $locked->current_assignment_id = null;
                $locked->current_owner_id = null;
                $locked->save();
            });

            $this->workflow->transition($locked, RequestStatus::PendingFinalApproval, $actor, 'اتمام کارشناسی');
            $this->audit->record('request.assignment_completed', $locked, 'success', [
                'assignment_id' => $assignment->id,
            ], $actor);

            event(new AssignmentCompleted($locked->id, $assignment->id));
        });
    }

    private function transfer(
        ServiceRequest $request,
        User $actor,
        ?int $toUserId,
        ?int $toUnitId,
        AssignmentType $type,
        ?string $reason,
        ?string $instructions,
    ): RequestAssignment {
        if ($toUserId === null && $toUnitId === null) {
            throw new WorkflowException('مقصد ارجاع باید یک کارشناس یا واحد سازمانی باشد.');
        }

        try {
            return DB::transaction(function () use ($request, $actor, $toUserId, $toUnitId, $type, $reason, $instructions): RequestAssignment {
                $locked = $this->lock($request);
                $this->assertStatusAllowsTransfer($locked, $type);

                $toUser = $this->activeUser($toUserId);
                $unit = $this->activeUnit($toUnitId) ?? ($toUser?->organizational_unit_id
                    ? OrganizationalUnit::query()->whereKey($toUser->organizational_unit_id)->where('is_active', true)->first()
                    : null);

                if ($toUser === null && $unit === null) {
                    throw new WorkflowException('مقصد ارجاع فعال نیست.');
                }

                $previous = RequestAssignment::query()
                    ->where('service_request_id', $locked->id)
                    ->where('status', AssignmentStatus::Active)
                    ->lockForUpdate()
                    ->first();

                $assignment = $locked->withinWorkflow(function () use ($locked, $actor, $toUser, $unit, $type, $reason, $instructions, $previous): RequestAssignment {
                    if ($previous) {
                        $previous->status = AssignmentStatus::Transferred;
                        $previous->active_lock = null;
                        $previous->completed_at = now();
                        $previous->save();
                    }

                    $assignment = RequestAssignment::query()->create([
                        'service_request_id' => $locked->id,
                        'parent_assignment_id' => $previous?->id,
                        'from_user_id' => $actor->id,
                        'from_unit_id' => $actor->organizational_unit_id,
                        'to_user_id' => $toUser?->id,
                        'to_unit_id' => $unit?->id,
                        'assignment_type' => $type,
                        'status' => AssignmentStatus::Active,
                        'active_lock' => $locked->id,
                        'instructions' => $instructions,
                        'reason' => $reason,
                    ]);

                    $locked->current_assignment_id = $assignment->id;
                    $locked->current_owner_id = $toUser?->id;
                    $locked->current_unit_id = $unit?->id;
                    $locked->save();

                    return $assignment;
                });

                $target = $toUser ? RequestStatus::Assigned : RequestStatus::AwaitingUnitClaim;

                if ($locked->status === RequestStatus::Reopened || $locked->status->canTransitionTo($target)) {
                    if ($locked->status === RequestStatus::Reopened) {
                        $this->workflow->transition($locked, $target, $actor, $reason);
                    } elseif ($locked->status !== $target) {
                        $this->workflow->transition($locked, $target, $actor, $reason);
                    }
                }

                $this->assertInvariant($locked->fresh(['activeAssignment']));
                $this->audit->record('request.assigned', $locked, 'success', [
                    'assignment_id' => $assignment->id,
                    'to_user_id' => $toUser?->id,
                    'to_unit_id' => $unit?->id,
                    'type' => $type->value,
                ], $actor);

                event(new ServiceRequestAssigned($locked->id, $assignment->id));

                return $assignment;
            });
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'active_lock')) {
                throw new WorkflowException('این درخواست قبلاً توسط کاربر دیگری تغییر کرده است.');
            }

            throw $exception;
        }
    }

    private function lock(ServiceRequest $request): ServiceRequest
    {
        $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->first();

        if ($locked === null) {
            throw new WorkflowException('درخواست پیدا نشد.');
        }

        return $locked;
    }

    private function activeAssignment(ServiceRequest $request): RequestAssignment
    {
        $assignment = RequestAssignment::query()
            ->where('service_request_id', $request->id)
            ->where('status', AssignmentStatus::Active)
            ->lockForUpdate()
            ->first();

        if ($assignment === null) {
            throw new WorkflowException('مأموریت فعالی برای این درخواست وجود ندارد.');
        }

        return $assignment;
    }

    private function assertStatusAllowsTransfer(ServiceRequest $request, AssignmentType $type): void
    {
        $allowed = match ($type) {
            AssignmentType::Initial, AssignmentType::UnitTransfer => [RequestStatus::PendingAssignment, RequestStatus::AwaitingUnitClaim, RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::WaitingCustomer],
            AssignmentType::Reassignment, AssignmentType::Claim => [RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::WaitingCustomer, RequestStatus::AwaitingUnitClaim],
            AssignmentType::Correction => [RequestStatus::PendingFinalApproval],
            AssignmentType::Reopen => [RequestStatus::Reopened],
        };

        if ($type === AssignmentType::Initial && $request->status !== RequestStatus::PendingAssignment && $request->status !== RequestStatus::AwaitingUnitClaim) {
            throw new WorkflowException('این درخواست در وضعیت فعلی قابل ارجاع اولیه نیست.');
        }

        if (! in_array($request->status, $allowed, true)) {
            throw new WorkflowException('این درخواست در وضعیت فعلی قابل ارجاع نیست.');
        }
    }

    private function activeUser(?int $userId): ?User
    {
        if ($userId === null) {
            return null;
        }

        $user = User::query()->whereKey($userId)->where('is_active', true)->first();

        if ($user === null) {
            throw new WorkflowException('کاربر مقصد فعال نیست.');
        }

        return $user;
    }

    private function activeUnit(?int $unitId): ?OrganizationalUnit
    {
        if ($unitId === null) {
            return null;
        }

        $unit = OrganizationalUnit::query()->whereKey($unitId)->where('is_active', true)->first();

        if ($unit === null) {
            throw new WorkflowException('واحد سازمانی مقصد فعال نیست.');
        }

        return $unit;
    }

    private function assertInvariant(ServiceRequest $request): void
    {
        $activeCount = $request->assignments()->where('status', AssignmentStatus::Active)->count();

        if ($activeCount > 1) {
            throw new WorkflowException('پرونده بیش از یک مأموریت فعال دارد.');
        }

        if (! $request->status->allowsNullOwner() && $request->current_owner_id === null && $request->status !== RequestStatus::AwaitingUnitClaim) {
            throw new WorkflowException('مسئول فعال پرونده مشخص نیست.');
        }

        if ($request->status === RequestStatus::AwaitingUnitClaim && ($request->current_unit_id === null || $request->current_owner_id !== null)) {
            throw new WorkflowException('ارجاع به واحد بدون مسئول مشخص، ناقص ثبت شده است.');
        }
    }
}
