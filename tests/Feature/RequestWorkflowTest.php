<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\MessageVisibility;
use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use App\Models\OrganizationalUnit;
use App\Models\RequestAssignment;
use App\Models\RequestMessage;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\StaffAlertNotification;
use App\Services\AssignmentService;
use App\Services\FinalApprovalService;
use App\Services\RequestMessageService;
use App\Services\RequestSubmissionService;
use App\Services\RequestViewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Tests\CrmTestCase;

class RequestWorkflowTest extends CrmTestCase
{
    public function test_request_moves_from_submission_to_closure_with_one_active_assignment(): void
    {
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();

        $request = app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مریم احمدی',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDays(3)->toDateString(),
            'invoice_number' => 'F-100',
            'product_name' => 'بشقاب چینی',
            'description' => 'یک عدد از بشقاب‌ها هنگام تحویل شکسته بود.',
            'fields' => [],
        ], $this->publicSubmissionFiles(), '09120000000', 'submit-key-1');

        $this->assertSame(RequestStatus::PendingAssignment, $request->status);
        $this->assertMatchesRegularExpression('/^RQ-[A-Z0-9]{10}$/', $request->tracking_code);
        $this->assertDatabaseHas('request_status_histories', [
            'service_request_id' => $request->id,
            'to_status' => RequestStatus::PendingAssignment->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
        ]);

        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, 'بررسی فروش', 'تخصیص اولیه');
        $request->refresh();

        $this->assertSame(RequestStatus::Assigned, $request->status);
        $this->assertSame($expert->id, $request->current_owner_id);
        $this->assertSame(1, $request->assignments()->where('status', AssignmentStatus::Active)->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $expert->id]);

        app(RequestViewService::class)->record($request->refresh(), $admin);
        $this->assertNull($request->currentAssignment->fresh()->first_viewed_at);

        app(RequestViewService::class)->record($request->refresh(), $expert);
        $request->refresh();
        $this->assertSame(RequestStatus::InProgress, $request->status);
        $this->assertNotNull($request->currentAssignment->first_viewed_at);
        $this->assertDatabaseHas('request_views', [
            'service_request_id' => $request->id,
            'user_id' => $expert->id,
            'kind' => 'assignee',
        ]);

        app(RequestMessageService::class)->addExpertNote($request, $expert, 'کالا از نظر فروش قابل بررسی است.');
        app(RequestMessageService::class)->addAdminNote($request, $admin, 'یادداشت محرمانه مدیر');
        app(RequestMessageService::class)->requestCustomerInformation($request, $expert, 'تصویر بسته‌بندی را ارسال کنید.');
        $request->refresh();
        $this->assertSame(RequestStatus::WaitingCustomer, $request->status);

        $png = UploadedFile::fake()->image('package.png');
        app(RequestSubmissionService::class)->receiveSupplement($request, [$png]);
        $request->refresh();
        $this->assertSame(RequestStatus::InProgress, $request->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $expert->id,
            'type' => StaffAlertNotification::class,
        ]);

        app(RequestMessageService::class)->addReviewResult($request, $expert, 'شکستگی تأیید شد.');
        app(AssignmentService::class)->complete($request, $expert, 'نتیجه کارشناسی: شکستگی تأیید شد.');
        $request->refresh();

        $this->assertSame(RequestStatus::PendingFinalApproval, $request->status);
        $this->assertNull($request->current_owner_id);
        $this->assertSame(0, $request->assignments()->where('status', AssignmentStatus::Active)->count());
        $this->assertSame(1, $request->assignments()->where('status', AssignmentStatus::Completed)->count());

        $this->expectException(AuthorizationException::class);
        app(FinalApprovalService::class)->approve($request, $expert, 'نتیجه', true);
    }

    public function test_manager_can_approve_reject_archive_and_reopen(): void
    {
        [$request, $admin, $expert] = $this->readyForApproval();

        $closed = app(FinalApprovalService::class)->approve($request, $admin, 'کالای جایگزین ارسال می‌شود.', true);
        $this->assertSame(RequestStatus::Closed, $closed->status);
        $this->assertNotNull($closed->approved_at);
        $this->assertNotNull($closed->closed_at);
        $this->assertDatabaseHas('request_status_histories', ['to_status' => RequestStatus::Approved->value]);
        $this->assertDatabaseHas('request_status_histories', ['to_status' => RequestStatus::Closed->value]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'request.approved']);

        $archived = app(FinalApprovalService::class)->archive($closed, $admin);
        $this->assertSame(RequestStatus::Archived, $archived->status);

        $reopened = app(FinalApprovalService::class)->reopen($archived, $admin, 'مشتری مدرک جدید دارد', $expert->id);
        $this->assertSame(RequestStatus::Assigned, $reopened->status);
        $this->assertSame($expert->id, $reopened->current_owner_id);
        $this->assertDatabaseHas('request_status_histories', ['to_status' => RequestStatus::Reopened->value]);
    }

    public function test_rejection_keeps_history_and_creates_a_new_active_assignment(): void
    {
        [$request, $admin, $expert] = $this->readyForApproval();

        $returned = app(FinalApprovalService::class)->reject($request, $admin, 'نتیجه کارشناسی ناقص است', $expert->id);

        $this->assertSame(RequestStatus::Assigned, $returned->status);
        $this->assertSame(1, $returned->assignments()->where('status', AssignmentStatus::Active)->count());
        $this->assertDatabaseHas('approval_decisions', ['decision' => 'rejected']);
        $this->assertNotNull($returned->assignments()->where('status', AssignmentStatus::Completed)->first());
    }

    public function test_reassignment_closes_previous_assignment_and_blocks_old_owner(): void
    {
        $admin = $this->staff('system_administrator');
        $first = $this->staff('expert');
        $second = $this->staff('expert');
        $unit = OrganizationalUnit::factory()->create();
        $request = ServiceRequest::factory()->create();

        app(AssignmentService::class)->assignInitial($request, $admin, $first->id, null, null, 'شروع');
        app(RequestViewService::class)->record($request->refresh(), $first);
        app(AssignmentService::class)->reassign($request->refresh(), $first, null, $unit->id, 'نیاز به بررسی انبار', 'موجودی کنترل شود');

        $request->refresh();
        $this->assertSame(RequestStatus::AwaitingUnitClaim, $request->status);
        $this->assertNull($request->current_owner_id);
        $this->assertSame($unit->id, $request->current_unit_id);
        $this->assertSame(1, $request->assignments()->where('status', AssignmentStatus::Transferred)->count());

        $lead = $this->staff('system_administrator', ['organizational_unit_id' => $unit->id]);
        app(AssignmentService::class)->claim($request->refresh(), $lead);
        $request->refresh();
        $this->assertSame($lead->id, $request->current_owner_id);

        $this->expectException(AuthorizationException::class);
        app(RequestMessageService::class)->addExpertNote($request, $first, 'نباید ثبت شود');
    }

    public function test_direct_status_change_is_rejected(): void
    {
        $request = ServiceRequest::factory()->create();

        $this->expectException(WorkflowException::class);
        $request->update(['status' => RequestStatus::Closed]);
    }

    public function test_duplicate_submission_key_does_not_create_another_request(): void
    {
        $type = RequestType::query()->where('slug', 'shortage')->firstOrFail();
        $payload = [
            'full_name' => 'رضا محمدی',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'دو قلم از فاکتور داخل بسته نبود.',
            'fields' => [],
        ];

        $files = $this->publicSubmissionFiles();
        $first = app(RequestSubmissionService::class)->submitPublic($payload, $files, '09121111111', 'same-key');
        $second = app(RequestSubmissionService::class)->submitPublic($payload, $files, '09121111111', 'same-key');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ServiceRequest::query()->count());
    }

    public function test_customer_visible_messages_are_separate_from_admin_notes(): void
    {
        $admin = $this->staff('system_administrator');
        $request = ServiceRequest::factory()->create();
        app(RequestMessageService::class)->addAdminNote($request, $admin, 'فقط مدیر');

        $this->assertSame(0, RequestMessage::query()->where('visibility', MessageVisibility::CustomerVisible)->count());
        $this->assertSame(1, RequestMessage::query()->where('visibility', MessageVisibility::AdminOnly)->count());
    }

    public function test_database_rejects_two_active_locks_for_one_request(): void
    {
        $request = ServiceRequest::factory()->create();
        $user = User::factory()->create();

        RequestAssignment::query()->create([
            'service_request_id' => $request->id,
            'to_user_id' => $user->id,
            'assignment_type' => 'initial',
            'status' => 'active',
            'active_lock' => $request->id,
        ]);

        $this->expectException(QueryException::class);

        RequestAssignment::query()->create([
            'service_request_id' => $request->id,
            'to_user_id' => $user->id,
            'assignment_type' => 'reassignment',
            'status' => 'active',
            'active_lock' => $request->id,
        ]);
    }

    /**
     * @return array{0: ServiceRequest, 1: User, 2: User}
     */
    private function readyForApproval(): array
    {
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $request = ServiceRequest::factory()->create();
        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, null, 'بررسی');
        app(RequestViewService::class)->record($request->refresh(), $expert);
        app(AssignmentService::class)->complete($request->refresh(), $expert, 'بررسی تمام شد');

        return [$request->refresh(), $admin, $expert];
    }
}
