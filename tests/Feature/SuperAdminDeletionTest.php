<?php

namespace Tests\Feature;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Services\AssignmentService;
use App\Services\AttachmentService;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use App\Services\RecordDeletionService;
use App\Services\RequestMessageService;
use App\Services\RequestViewService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\CrmTestCase;

class SuperAdminDeletionTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('local');
    }

    public function test_super_admin_deletes_request_with_history_files_and_stock(): void
    {
        $root = $this->staff('super_admin');
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $request = ServiceRequest::factory()->create();
        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, null, 'شروع');
        app(RequestViewService::class)->record($request->refresh(), $expert);
        app(RequestMessageService::class)->addExpertNote($request->refresh(), $expert, 'یادداشت کارشناس');
        $attachment = app(AttachmentService::class)->store(
            UploadedFile::fake()->image('photo.jpg'),
            $request->refresh(),
            AttachmentCategory::Internal,
            AttachmentVisibility::Internal,
            $expert,
            null,
            $request->current_assignment_id,
        );
        $product = WarehouseProduct::factory()->create(['quantity' => 3]);
        app(InventoryService::class)->allocate($product, $request->refresh(), $admin);
        Storage::disk('local')->assertExists($attachment->storage_path);

        app(RecordDeletionService::class)->delete($request->refresh(), $root);

        $this->assertModelMissing($request);
        $this->assertDatabaseMissing('request_messages', ['service_request_id' => $request->id]);
        $this->assertDatabaseMissing('request_assignments', ['service_request_id' => $request->id]);
        $this->assertDatabaseMissing('inventory_allocations', ['service_request_id' => $request->id]);
        $this->assertSame(3, $product->refresh()->quantity);
        Storage::disk('local')->assertMissing($attachment->storage_path);
        $this->assertDatabaseHas('audit_logs', ['event' => 'record.deleted', 'auditable_id' => $request->id]);
    }

    public function test_only_super_admin_can_delete_requests_and_users(): void
    {
        $admin = $this->staff('system_administrator');
        $request = ServiceRequest::factory()->create();

        $this->assertFalse($admin->can('delete', $request));
        $this->assertFalse($admin->can('delete', $this->staff('expert')));

        $this->expectException(AuthorizationException::class);
        app(RecordDeletionService::class)->delete($request, $admin);
    }

    public function test_user_with_history_is_deleted_and_references_are_cleared(): void
    {
        $root = $this->staff('super_admin');
        $admin = $this->staff('system_administrator');
        $first = $this->staff('expert', ['email' => 'old@example.com']);
        $second = $this->staff('expert');
        $request = ServiceRequest::factory()->create();
        app(AssignmentService::class)->assignInitial($request, $admin, $first->id, null, null, 'شروع');
        app(RequestViewService::class)->record($request->refresh(), $first);
        app(RequestMessageService::class)->addExpertNote($request->refresh(), $first, 'یادداشت قبل از ارجاع');

        try {
            app(RecordDeletionService::class)->delete($first, $root);
            $this->fail('A user owning an open request was deleted.');
        } catch (WorkflowException $exception) {
            $this->assertStringContainsString('پرونده باز', $exception->getMessage());
        }

        app(AssignmentService::class)->reassign($request->refresh(), $first, $second->id, null, 'انتقال', null);
        app(RecordDeletionService::class)->delete($first->refresh(), $root);

        $this->assertDatabaseMissing('users', ['id' => $first->id]);
        $this->assertDatabaseMissing('request_messages', ['sender_id' => $first->id]);
        $this->assertDatabaseMissing('request_assignments', ['to_user_id' => $first->id]);
        $this->assertSame($second->id, $request->refresh()->current_owner_id);
        $this->assertTrue($request->messages()->where('body', 'یادداشت قبل از ارجاع')->whereNull('sender_id')->exists());
        $this->assertNotNull(User::factory()->create(['email' => 'old@example.com'])->id);
    }

    public function test_self_last_super_admin_and_protected_records(): void
    {
        $root = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))->firstOrFail();

        $this->assertFalse($root->can('delete', $root));
        $this->assertFalse($root->can('delete', Role::findByName('super_admin')));

        $type = RequestType::query()->firstOrFail();
        ServiceRequest::factory()->create(['request_type_id' => $type->id]);

        $this->expectException(WorkflowException::class);
        app(RecordDeletionService::class)->delete($type, $root);
    }

    public function test_warehouse_delete_cascades_products_and_allocations(): void
    {
        $root = $this->staff('super_admin');
        $warehouse = Warehouse::factory()->create();
        $product = WarehouseProduct::factory()->for($warehouse)->create(['quantity' => 2]);
        app(InventoryService::class)->allocate($product, ServiceRequest::factory()->create(), $root);

        $this->assertFalse($this->staff('warehouse_manager')->can('delete', $warehouse));

        app(RecordDeletionService::class)->delete($warehouse, $root);

        $this->assertModelMissing($warehouse);
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('inventory_allocations', 0);
    }

    public function test_panel_delete_actions_for_super_admin(): void
    {
        $root = $this->staff('super_admin');
        $expert = $this->staff('expert');
        $requests = ServiceRequest::factory()->count(2)->create();
        $single = ServiceRequest::factory()->create();
        $this->actingAs($root);

        Livewire::test(ViewServiceRequest::class, ['record' => $single->id])
            ->callAction('delete')
            ->assertHasNoActionErrors();
        $this->assertModelMissing($single);

        Livewire::test(ListServiceRequests::class)
            ->selectTableRecords($requests->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoActionErrors();
        $this->assertDatabaseCount('service_requests', 0);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($root))
            ->callAction(TestAction::make('delete')->table($expert));
        $this->assertDatabaseMissing('users', ['id' => $expert->id]);

        $log = app(AuditLogService::class)->record('test.event', null, 'success', [], $root);
        Livewire::test(ListAuditLogs::class)->callAction(TestAction::make('delete')->table($log));
        $this->assertModelMissing($log);
    }

    public function test_non_super_admin_sees_no_delete_on_requests(): void
    {
        $admin = $this->staff('system_administrator');
        $request = ServiceRequest::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])->assertActionHidden('delete');
        Livewire::test(ListServiceRequests::class)->assertActionHidden(TestAction::make('delete')->table($request));
    }
}
