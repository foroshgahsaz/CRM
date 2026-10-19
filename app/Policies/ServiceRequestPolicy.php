<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('requests.view_all') || $user->can('requests.view_related');
    }

    public function view(User $user, ServiceRequest $request): bool
    {
        if ($user->can('requests.view_all')) {
            return true;
        }

        if (! $user->can('requests.view_related')) {
            return false;
        }

        if ($request->current_owner_id === $user->id) {
            return true;
        }

        if ($request->assignments()->where('to_user_id', $user->id)->exists()) {
            return true;
        }

        return $user->can('requests.assign')
            && $user->organizational_unit_id !== null
            && $request->current_unit_id === $user->organizational_unit_id
            && $request->status === RequestStatus::AwaitingUnitClaim;
    }

    public function viewSensitive(User $user, ServiceRequest $request): bool
    {
        return $this->view($user, $request) && $user->can('requests.view_sensitive');
    }

    public function viewAdminNotes(User $user, ServiceRequest $request): bool
    {
        return $this->view($user, $request) && ($user->can('requests.view_sensitive') || $user->can('requests.add_admin_note'));
    }

    public function create(User $user): bool
    {
        return $user->can('requests.create_internal');
    }

    public function update(User $user, ServiceRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, ServiceRequest $request): bool
    {
        return false;
    }

    public function assign(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.assign')) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if (! in_array($request->status, [RequestStatus::PendingAssignment, RequestStatus::AwaitingUnitClaim], true)) {
            return Response::deny('این درخواست در وضعیت فعلی قابل ارجاع نیست.');
        }

        return Response::allow();
    }

    public function reassign(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.reassign') || ! $this->view($user, $request)) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if ($request->isReadOnly()) {
            return Response::deny('پرونده بایگانی‌شده فقط قابل مشاهده است.');
        }

        $owns = $user->can('requests.view_all') || $request->current_owner_id === $user->id;

        if (! $owns) {
            return Response::deny('فقط مسئول فعال می‌تواند پرونده را ارجاع دهد.');
        }

        if (! in_array($request->status, [RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::WaitingCustomer], true)) {
            return Response::deny('این درخواست در وضعیت فعلی قابل ارجاع نیست.');
        }

        return Response::allow();
    }

    public function claim(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.assign') || $request->status !== RequestStatus::AwaitingUnitClaim) {
            return Response::deny('این درخواست در وضعیت فعلی قابل پذیرش نیست.');
        }

        if ($user->organizational_unit_id === null || $user->organizational_unit_id !== $request->current_unit_id) {
            return Response::deny('پذیرش فقط برای عضو همان واحد مجاز است.');
        }

        return Response::allow();
    }

    public function reply(User $user, ServiceRequest $request): Response
    {
        return $this->activeOwner($user, $request, 'requests.reply', [RequestStatus::Assigned, RequestStatus::InProgress]);
    }

    public function addInternalNote(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.add_internal_note') || ! $this->view($user, $request)) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        return Response::allow();
    }

    public function addAdminNote(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.add_admin_note') || ! $this->view($user, $request)) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        return Response::allow();
    }

    public function requestCustomerInfo(User $user, ServiceRequest $request): Response
    {
        return $this->activeOwner($user, $request, 'requests.request_customer_info', [RequestStatus::InProgress]);
    }

    public function uploadInternalAttachment(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.upload_internal_attachment') || ! $this->view($user, $request) || $request->isReadOnly()) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if ($user->can('requests.view_all') || $request->current_owner_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('بارگذاری پیوست فقط برای مسئول فعال مجاز است.');
    }

    public function complete(User $user, ServiceRequest $request): Response
    {
        return $this->activeOwner($user, $request, 'requests.complete_assignment', [RequestStatus::InProgress]);
    }

    public function approve(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.approve')) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if ($request->status !== RequestStatus::PendingFinalApproval) {
            return Response::deny('این درخواست در وضعیت فعلی قابل تأیید نهایی نیست.');
        }

        return Response::allow();
    }

    public function rejectApproval(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.reject_approval')) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if ($request->status !== RequestStatus::PendingFinalApproval) {
            return Response::deny('این درخواست در وضعیت فعلی قابل رد تأیید نیست.');
        }

        return Response::allow();
    }

    public function close(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.close')) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if (! in_array($request->status, [RequestStatus::Approved, RequestStatus::PendingFinalApproval], true)) {
            return Response::deny('بستن پرونده در این وضعیت مجاز نیست.');
        }

        return Response::allow();
    }

    public function archive(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.archive') || $request->status !== RequestStatus::Closed) {
            return Response::deny('فقط پرونده بسته‌شده قابل بایگانی است.');
        }

        return Response::allow();
    }

    public function reopen(User $user, ServiceRequest $request): Response
    {
        if (! $user->can('requests.reopen')) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if (! in_array($request->status, [RequestStatus::Closed, RequestStatus::Archived], true)) {
            return Response::deny('این پرونده در وضعیت فعلی قابل بازگشایی نیست.');
        }

        return Response::allow();
    }

    public function export(User $user): bool
    {
        return $user->can('requests.export');
    }

    /**
     * @param  list<RequestStatus>  $statuses
     */
    private function activeOwner(User $user, ServiceRequest $request, string $permission, array $statuses): Response
    {
        if (! $user->can($permission)) {
            return Response::deny('دسترسی لازم برای این عملیات وجود ندارد.');
        }

        if ($request->isReadOnly()) {
            return Response::deny('پرونده بایگانی‌شده فقط قابل مشاهده است.');
        }

        $assignment = $request->currentAssignment;

        if ($request->current_owner_id !== $user->id || $assignment === null || ! $assignment->isActive()) {
            return Response::deny('این عملیات فقط برای مسئول فعال پرونده مجاز است.');
        }

        if (! in_array($request->status, $statuses, true)) {
            return Response::deny('این درخواست در وضعیت فعلی قابل این عملیات نیست.');
        }

        return Response::allow();
    }
}
