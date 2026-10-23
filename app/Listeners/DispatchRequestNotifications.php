<?php

namespace App\Listeners;

use App\Events\AssignmentCompleted;
use App\Events\AssignmentFirstViewed;
use App\Events\CustomerInformationRequested;
use App\Events\CustomerSupplementReceived;
use App\Events\InventoryAllocated;
use App\Events\ServiceRequestApproved;
use App\Events\ServiceRequestAssigned;
use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestRejected;
use App\Events\ServiceRequestSubmitted;
use App\Models\InventoryAllocation;
use App\Models\RequestAssignment;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingsService;
use App\Services\SmsService;

class DispatchRequestNotifications
{
    public function __construct(
        private NotificationService $notifications,
        private SmsService $sms,
        private SettingsService $settings,
    ) {}

    public function handleSubmitted(ServiceRequestSubmitted $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->notifications->notify(
            $this->notifications->usersWith('requests.assign'),
            'request.submitted',
            'درخواست جدید ثبت شد',
            'درخواست '.$request->tracking_code.' منتظر تخصیص است.',
            $request,
            dedupe: 'submitted:'.$request->id,
        );

        $this->queueCustomerSms($request, 'request_submitted', 'submitted-sms:'.$request->id, [
            'tracking_code' => $request->tracking_code,
            'company_name' => (string) $this->settings->get('company_name'),
        ]);
    }

    public function handleAssigned(ServiceRequestAssigned $event): void
    {
        $request = $this->request($event->serviceRequestId);
        $assignment = RequestAssignment::query()->findOrFail($event->assignmentId);
        $recipients = $assignment->to_user_id
            ? User::query()->whereKey($assignment->to_user_id)->where('is_active', true)->get()
            : User::query()->permission('requests.assign')->where('is_active', true)->where('organizational_unit_id', $assignment->to_unit_id)->get();

        $this->notifications->notify(
            $recipients,
            'request.assigned',
            'درخواست به شما ارجاع شد',
            'پرونده '.$request->tracking_code.' برای رسیدگی ارجاع شده است.',
            $request,
            $assignment->from_user_id,
            'assignment:'.$assignment->id,
        );
    }

    public function handleFirstView(AssignmentFirstViewed $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->notifications->notify(
            $this->notifications->usersWith('requests.assign'),
            'request.first_view',
            'مسئول پرونده آن را مشاهده کرد',
            'اولین مشاهده پرونده '.$request->tracking_code.' ثبت شد.',
            $request,
            $event->userId,
            'first-view:'.$event->assignmentId,
        );
    }

    public function handleSupplement(CustomerSupplementReceived $event): void
    {
        $request = $this->request($event->serviceRequestId);
        $owner = $request->currentOwner;

        if ($owner === null) {
            return;
        }

        $this->notifications->notify(
            collect([$owner]),
            'request.supplement',
            'مدرک تکمیلی دریافت شد',
            'مشتری برای پرونده '.$request->tracking_code.' مدرک جدید فرستاد.',
            $request,
            dedupe: 'supplement:'.$request->id.':'.$request->attachments()->count(),
        );
    }

    public function handleCompleted(AssignmentCompleted $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->notifications->notify(
            $this->notifications->usersWith('requests.approve'),
            'request.pending_approval',
            'پرونده منتظر تأیید نهایی است',
            'کارشناسی پرونده '.$request->tracking_code.' تمام شده است.',
            $request,
            dedupe: 'approval:'.$event->assignmentId,
        );
    }

    public function handleRejected(ServiceRequestRejected $event): void
    {
        $request = $this->request($event->serviceRequestId);
        $assignment = RequestAssignment::query()->findOrFail($event->assignmentId);

        if ($assignment->to_user_id === null) {
            return;
        }

        $this->notifications->notify(
            User::query()->whereKey($assignment->to_user_id)->get(),
            'request.rejected',
            'پرونده برای اصلاح بازگشت',
            'تأیید نهایی پرونده '.$request->tracking_code.' رد شد.',
            $request,
            $assignment->from_user_id,
            'rejected:'.$assignment->id,
        );
    }

    public function handleApproved(ServiceRequestApproved $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->notifications->notify(
            $this->notifications->usersWith('requests.assign'),
            'request.approved',
            'درخواست تأیید شد',
            'پرونده '.$request->tracking_code.' تأیید نهایی شد.',
            $request,
            $request->approved_by,
            'approved:'.$request->id,
        );
    }

    public function handleClosed(ServiceRequestClosed $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->queueCustomerSms($request, 'request_closed', 'closed-sms:'.$request->id, [
            'tracking_code' => $request->tracking_code,
            'company_name' => (string) $this->settings->get('company_name'),
            'public_status' => $request->status->publicLabel(),
        ]);
    }

    public function handleInfo(CustomerInformationRequested $event): void
    {
        $request = $this->request($event->serviceRequestId);

        $this->queueCustomerSms($request, 'customer_info_requested', 'info-sms:'.$event->messageId, [
            'tracking_code' => $request->tracking_code,
            'company_name' => (string) $this->settings->get('company_name'),
        ]);
    }

    public function handleInventoryAllocated(InventoryAllocated $event): void
    {
        $request = $this->request($event->serviceRequestId);
        $allocation = InventoryAllocation::query()->findOrFail($event->allocationId);
        $recipients = $request->currentOwner
            ? collect([$request->currentOwner])
            : $this->notifications->usersWith('requests.assign');

        $this->notifications->notify(
            $recipients,
            'inventory.allocated',
            'کالا از انبار ارجاع شد',
            'یک عدد «'.$allocation->product_name_snapshot.'» از '.$allocation->warehouse_name_snapshot.' برای پرونده '.$request->tracking_code.' کنار گذاشته شد.',
            $request,
            $allocation->allocated_by,
            'inventory:'.$allocation->id,
        );
    }

    private function request(int $id): ServiceRequest
    {
        return ServiceRequest::query()->with(['customer', 'currentOwner'])->findOrFail($id);
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    private function queueCustomerSms(ServiceRequest $request, string $eventKey, string $key, array $variables): void
    {
        $mobile = $request->customer?->mobile;

        if (! is_string($mobile) || $mobile === '') {
            return;
        }

        $this->sms->queue($mobile, $eventKey, $variables, $request->id, $key);
    }
}
