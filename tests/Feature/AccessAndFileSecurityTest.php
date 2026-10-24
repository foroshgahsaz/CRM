<?php

namespace Tests\Feature;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use App\Models\CustomerAccessSession;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Services\AttachmentService;
use App\Services\CustomerAccessService;
use App\Services\RequestMessageService;
use App\Services\RequestSubmissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CrmTestCase;

class AccessAndFileSecurityTest extends CrmTestCase
{
    public function test_inactive_staff_cannot_open_the_panel(): void
    {
        $user = $this->staff('system_administrator', ['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_cannot_open_staff_pages_or_files(): void
    {
        $attachment = $this->attachment();

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get(route('staff.attachments.show', $attachment))->assertRedirect('/admin/login');
    }

    public function test_unrelated_expert_cannot_download_a_file_or_approve(): void
    {
        $expert = $this->staff('expert');
        $attachment = $this->attachment();

        $this->actingAs($expert)->get(route('staff.attachments.show', $attachment))->assertForbidden();
        $this->actingAs($expert)->get('/admin/service-requests/'.$attachment->service_request_id)->assertNotFound();
    }

    public function test_customer_cannot_read_internal_files_or_another_case(): void
    {
        $visible = $this->attachment(AttachmentVisibility::CustomerVisible);
        $internal = $this->attachment(AttachmentVisibility::Internal, $visible->serviceRequest);
        $other = $this->attachment();
        app(RequestMessageService::class)->addAdminNote($visible->serviceRequest, $this->staff('system_administrator'), 'یادداشت محرمانه مدیر');

        $token = 'customer-token-one';
        CustomerAccessSession::query()->create([
            'service_request_id' => $visible->service_request_id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(20),
        ]);

        $this->withCookie(CustomerAccessService::COOKIE, $token)
            ->get(route('track.show'))
            ->assertOk()
            ->assertDontSee('یادداشت محرمانه مدیر');

        $this->withCookie(CustomerAccessService::COOKIE, $token)
            ->get(route('track.attachments.show', $visible))
            ->assertOk();

        $this->withCookie(CustomerAccessService::COOKIE, $token)
            ->get(route('track.attachments.show', $internal))
            ->assertNotFound();

        $this->withCookie(CustomerAccessService::COOKIE, $token)
            ->get(route('track.attachments.show', $other))
            ->assertNotFound();
    }

    public function test_private_storage_rejects_path_traversal_and_is_not_publicly_served(): void
    {
        $admin = $this->staff('super_admin');
        $request = $this->makeRequest();
        $attachment = RequestAttachment::query()->create([
            'service_request_id' => $request->id,
            'category' => AttachmentCategory::Internal,
            'original_name' => 'secret.pdf',
            'storage_path' => '../.env',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'visibility' => AttachmentVisibility::Internal,
        ]);

        $this->assertFalse((bool) config('filesystems.disks.local.serve'));
        $this->actingAs($admin)->get(route('staff.attachments.show', $attachment))->assertNotFound();
        Storage::disk('local')->assertMissing('attachments/public-link');
    }

    private function attachment(AttachmentVisibility $visibility = AttachmentVisibility::CustomerVisible, ?ServiceRequest $request = null): RequestAttachment
    {
        $request ??= $this->makeRequest();
        $file = UploadedFile::fake()->image('invoice.jpg');

        return app(AttachmentService::class)->store(
            $file,
            $request,
            $visibility === AttachmentVisibility::Internal ? AttachmentCategory::Internal : AttachmentCategory::CustomerInitial,
            $visibility,
            null,
            $request->customer,
        );
    }

    private function makeRequest(): ServiceRequest
    {
        $type = RequestType::query()->where('slug', 'breakage')->firstOrFail();

        $mobile = '0912'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        return app(RequestSubmissionService::class)->submitPublic([
            'full_name' => 'مشتری فایل',
            'request_type_id' => $type->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'description' => 'شرح کافی برای پرونده فایل آزمایشی '.uniqid(),
            'fields' => [],
        ], $this->publicSubmissionFiles(), $mobile, 'file-'.uniqid());
    }
}
