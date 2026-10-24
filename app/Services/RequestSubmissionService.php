<?php

namespace App\Services;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Events\CustomerSupplementReceived;
use App\Events\ServiceRequestSubmitted;
use App\Exceptions\WorkflowException;
use App\Models\Customer;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\MobileNumber;
use App\Support\TrackingCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestSubmissionService
{
    public function __construct(
        private TrackingCode $trackingCodes,
        private DynamicFieldService $fields,
        private AttachmentService $attachments,
        private SlaService $sla,
        private AuditLogService $audit,
        private WorkflowService $workflow,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $files
     */
    public function submitPublic(array $input, array $files, string $mobile, string $submissionKey): ServiceRequest
    {
        $mobile = MobileNumber::normalize($mobile);

        if ($mobile === null) {
            throw new WorkflowException('شماره موبایل تأییدشده معتبر نیست.');
        }

        return $this->persist($input, $files, $mobile, $submissionKey, null);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $files
     */
    public function submitInternal(User $actor, array $input, array $files): ServiceRequest
    {
        Gate::forUser($actor)->authorize('create', ServiceRequest::class);

        $mobile = MobileNumber::normalize((string) ($input['mobile'] ?? ''));

        if ($mobile === null) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل معتبر نیست.']);
        }

        $request = $this->persist($input, $files, $mobile, 'staff-'.Str::uuid(), $actor);
        $this->audit->record('request.created_internal', $request, 'success', [], $actor);

        return $request;
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function receiveSupplement(ServiceRequest $request, array $files): void
    {
        if ($request->status !== RequestStatus::WaitingCustomer) {
            throw new WorkflowException('در وضعیت فعلی، ارسال مدرک تکمیلی مجاز نیست.');
        }

        if ($files === []) {
            throw ValidationException::withMessages(['attachments' => 'حداقل یک فایل انتخاب کنید.']);
        }

        DB::transaction(function () use ($request, $files): void {
            $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RequestStatus::WaitingCustomer) {
                throw new WorkflowException('در وضعیت فعلی، ارسال مدرک تکمیلی مجاز نیست.');
            }

            foreach ($files as $file) {
                $this->attachments->store(
                    $file,
                    $locked,
                    AttachmentCategory::CustomerSupplement,
                    AttachmentVisibility::CustomerVisible,
                    null,
                    $locked->customer,
                    $locked->current_assignment_id,
                );
            }

            if ($locked->status->canTransitionTo(RequestStatus::InProgress)) {
                $this->workflow->transition($locked, RequestStatus::InProgress, null, 'دریافت مدرک تکمیلی مشتری');
            }

            $this->audit->record('request.supplement_received', $locked, 'success', [
                'files' => count($files),
            ]);

            event(new CustomerSupplementReceived($locked->id));
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $files
     */
    private function persist(array $input, array $files, string $mobile, string $submissionKey, ?User $actor): ServiceRequest
    {
        $existing = ServiceRequest::query()->where('submission_key', $submissionKey)->first();

        if ($existing) {
            return $existing;
        }

        $type = RequestType::query()->whereKey($input['request_type_id'] ?? 0)->where('is_active', true)->first();

        if ($type === null) {
            throw ValidationException::withMessages(['request_type_id' => 'نوع درخواست فعال نیست.']);
        }

        $fieldValues = $this->fields->validate($type, ['fields' => $input['fields'] ?? []]);

        if ($actor === null) {
            $open = app(CustomerSubmissionGuard::class)->openRequestForMobile($mobile);

            if ($open !== null) {
                throw ValidationException::withMessages([
                    'mobile' => 'برای این شماره موبایل یک درخواست باز دارید. از پیگیری با کد '.$open->tracking_code.' استفاده کنید.',
                ]);
            }

            if ($files === []) {
                throw ValidationException::withMessages([
                    'attachments' => 'بارگذاری حداقل یک مدرک الزامی است.',
                ]);
            }
        }

        return DB::transaction(function () use ($input, $files, $mobile, $submissionKey, $actor, $type, $fieldValues): ServiceRequest {
            $duplicate = ServiceRequest::query()->where('submission_key', $submissionKey)->lockForUpdate()->first();

            if ($duplicate) {
                return $duplicate;
            }

            $customer = Customer::query()->firstOrCreate(
                ['mobile' => $mobile],
                ['full_name' => $input['full_name'], 'mobile_verified_at' => now()],
            );

            $customer->fill([
                'full_name' => $input['full_name'],
                'mobile_verified_at' => $customer->mobile_verified_at ?? now(),
            ])->save();

            $request = new ServiceRequest([
                'tracking_code' => $this->trackingCodes->generate(),
                'customer_id' => $customer->id,
                'request_type_id' => $type->id,
                'purchase_date' => $input['purchase_date'],
                'invoice_number' => $input['invoice_number'] ?? null,
                'product_name' => $input['product_name'] ?? null,
                'description' => $input['description'],
                'status' => RequestStatus::PendingAssignment,
                'priority' => $input['priority'] ?? RequestPriority::Normal,
                'submitted_at' => now(),
                'submission_key' => $submissionKey,
            ]);

            $this->sla->apply($request);
            $request->save();

            RequestStatusHistory::query()->create([
                'service_request_id' => $request->id,
                'from_status' => null,
                'to_status' => RequestStatus::PendingAssignment->value,
                'changed_by' => $actor?->id,
                'reason' => $actor ? 'ثبت داخلی درخواست' : 'ثبت درخواست توسط مشتری',
                'created_at' => now(),
            ]);

            $this->fields->store($request, $fieldValues);

            foreach ($files as $file) {
                $this->attachments->store(
                    $file,
                    $request,
                    AttachmentCategory::CustomerInitial,
                    AttachmentVisibility::CustomerVisible,
                    $actor,
                    $customer,
                );
            }

            $this->audit->record('request.submitted', $request, 'success', [], $actor);
            event(new ServiceRequestSubmitted($request->id));

            return $request;
        });
    }

}
