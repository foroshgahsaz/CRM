<?php

namespace App\Services;

use App\Enums\MessageType;
use App\Enums\MessageVisibility;
use App\Enums\RequestStatus;
use App\Events\CustomerInformationRequested;
use App\Exceptions\WorkflowException;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RequestMessageService
{
    public function __construct(
        private WorkflowService $workflow,
        private AuditLogService $audit,
    ) {}

    public function addExpertNote(ServiceRequest $request, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('reply', $request);

        return $this->store($request, $actor, $body, MessageType::ExpertNote, MessageVisibility::Internal, false);
    }

    public function addReviewResult(ServiceRequest $request, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('reply', $request);

        return $this->store($request, $actor, $body, MessageType::ReviewResult, MessageVisibility::Internal, true);
    }

    public function addInternalNote(ServiceRequest $request, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('addInternalNote', $request);

        return $this->store($request, $actor, $body, MessageType::ExpertNote, MessageVisibility::Internal, false);
    }

    public function addAdminNote(ServiceRequest $request, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('addAdminNote', $request);

        $message = $this->store($request, $actor, $body, MessageType::AdminNote, MessageVisibility::AdminOnly, false);
        $this->audit->record('request.admin_note', $request, 'success', ['message_id' => $message->id], $actor);

        return $message;
    }

    public function requestCustomerInformation(ServiceRequest $request, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('requestCustomerInfo', $request);

        return DB::transaction(function () use ($request, $actor, $body): RequestMessage {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RequestStatus::InProgress) {
                throw new WorkflowException('درخواست مدرک فقط هنگام رسیدگی فعال ممکن است.');
            }

            $message = $this->store($locked, $actor, $body, MessageType::InfoRequest, MessageVisibility::CustomerVisible, true);
            $locked->withinWorkflow(function () use ($locked, $body): void {
                $locked->customer_visible_summary = $body;
                $locked->save();
            });
            $this->workflow->transition($locked, RequestStatus::WaitingCustomer, $actor, 'درخواست مدرک تکمیلی از مشتری');
            $this->audit->record('request.customer_info_requested', $locked, 'success', ['message_id' => $message->id], $actor);
            event(new CustomerInformationRequested($locked->id, $message->id));

            return $message;
        });
    }

    public function correct(RequestMessage $message, User $actor, string $body): RequestMessage
    {
        Gate::forUser($actor)->authorize('reply', $message->serviceRequest);

        return $this->store(
            $message->serviceRequest,
            $actor,
            $body,
            MessageType::Correction,
            $message->visibility,
            false,
            $message->id,
        );
    }

    private function store(
        ServiceRequest $request,
        User $actor,
        string $body,
        MessageType $type,
        MessageVisibility $visibility,
        bool $isFinal,
        ?int $correctsId = null,
    ): RequestMessage {
        $body = trim($body);

        if ($body === '') {
            throw new WorkflowException('متن پیام خالی است.');
        }

        return RequestMessage::query()->create([
            'service_request_id' => $request->id,
            'assignment_id' => $request->current_assignment_id,
            'sender_id' => $actor->id,
            'message_type' => $type,
            'visibility' => $visibility,
            'body' => $body,
            'corrects_message_id' => $correctsId,
            'is_final' => $isFinal,
        ]);
    }
}
