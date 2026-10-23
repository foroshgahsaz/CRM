<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\InventoryAllocations\Pages\ListInventoryAllocations;
use App\Filament\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Resources\WarehouseProducts\Pages\CreateWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Pages\ListWarehouseProducts;
use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\ViewWarehouse;
use App\Filament\Resources\Warehouses\RelationManagers\ProductsRelationManager;
use App\Models\InventoryAllocation;
use App\Models\ServiceRequest;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Services\AssignmentService;
use App\Services\InventoryService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\CrmTestCase;

class WarehouseInventoryTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_each_allocation_takes_one_unit_and_records_who_and_which_request(): void
    {
        $manager = $this->staff('warehouse_manager');
        $product = WarehouseProduct::factory()->create(['name' => 'بشقاب مدل X', 'code' => 'PLT-X', 'quantity' => 2]);
        $first = ServiceRequest::factory()->create();
        $second = ServiceRequest::factory()->create();
        $service = app(InventoryService::class);

        $allocation = $service->allocate($product, $first, $manager, 'برای جایگزینی');
        $service->allocate($product->refresh(), $second, $manager);

        $this->assertSame(0, $product->refresh()->quantity);
        $this->assertSame($first->id, $allocation->service_request_id);
        $this->assertSame($manager->id, $allocation->allocated_by);
        $this->assertSame('بشقاب مدل X', $allocation->product_name_snapshot);
        $this->assertSame('PLT-X', $allocation->product_code_snapshot);
        $this->assertSame(2, $product->activeAllocations()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'inventory.allocated']);

        $this->expectException(AuthorizationException::class);
        $service->allocate($product->refresh(), ServiceRequest::factory()->create(), $manager);
    }

    public function test_out_of_stock_is_rejected_even_with_a_stale_model(): void
    {
        $manager = $this->staff('warehouse_manager');
        $product = WarehouseProduct::factory()->create(['quantity' => 1]);
        $stale = WarehouseProduct::query()->findOrFail($product->id);

        app(InventoryService::class)->allocate($product, ServiceRequest::factory()->create(), $manager);

        $this->expectException(WorkflowException::class);
        app(InventoryService::class)->allocate($stale, ServiceRequest::factory()->create(), $manager);
    }

    public function test_closed_request_and_inactive_warehouse_cannot_receive_stock(): void
    {
        $manager = $this->staff('warehouse_manager');
        $product = WarehouseProduct::factory()->create(['quantity' => 5]);
        $closed = ServiceRequest::factory()->create(['status' => RequestStatus::Closed]);

        try {
            app(InventoryService::class)->allocate($product, $closed, $manager);
            $this->fail('Closed request accepted an allocation.');
        } catch (WorkflowException) {
            $this->assertSame(5, $product->refresh()->quantity);
        }

        $product->warehouse->update(['is_active' => false]);

        $this->expectException(WorkflowException::class);
        app(InventoryService::class)->allocate($product->refresh(), ServiceRequest::factory()->create(), $manager);
    }

    public function test_cancelling_returns_the_unit_once(): void
    {
        $manager = $this->staff('warehouse_manager');
        $product = WarehouseProduct::factory()->create(['quantity' => 1]);
        $allocation = app(InventoryService::class)->allocate($product, ServiceRequest::factory()->create(), $manager);

        app(InventoryService::class)->cancel($allocation, $manager, 'اشتباه ثبت شد');

        $this->assertSame(1, $product->refresh()->quantity);
        $this->assertFalse($allocation->refresh()->isActive());
        $this->assertSame($manager->id, $allocation->cancelled_by);

        $this->expectException(AuthorizationException::class);
        app(InventoryService::class)->cancel($allocation, $manager, 'دوباره');
    }

    public function test_expert_can_view_but_not_manage_or_allocate(): void
    {
        $expert = $this->staff('expert');
        $product = WarehouseProduct::factory()->create();

        $this->assertTrue($expert->can('viewAny', Warehouse::class));
        $this->assertTrue($expert->can('viewAny', WarehouseProduct::class));
        $this->assertFalse($expert->can('create', Warehouse::class));
        $this->assertFalse($expert->can('create', WarehouseProduct::class));

        $this->expectException(AuthorizationException::class);
        app(InventoryService::class)->allocate($product, ServiceRequest::factory()->create(), $expert);
    }

    public function test_owner_is_notified_and_sees_allocation_on_request_page(): void
    {
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $manager = $this->staff('warehouse_manager');
        $request = ServiceRequest::factory()->create();
        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, null, 'شروع');
        $product = WarehouseProduct::factory()->create(['name' => 'بشقاب مدل X', 'code' => 'PLT-X']);

        app(InventoryService::class)->allocate($product, $request->refresh(), $manager);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $expert->id]);

        $this->actingAs($expert);
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->assertOk()
            ->assertSee('کالای ارجاع‌شده از انبار')
            ->assertSee('بشقاب مدل X')
            ->assertSee($manager->name)
            ->assertActionHidden('allocateFromWarehouse');
    }

    public function test_allocation_can_be_made_from_request_page(): void
    {
        $request = ServiceRequest::factory()->create();
        $product = WarehouseProduct::factory()->create(['quantity' => 3]);

        $this->actingAs($this->staff('super_admin'));
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->callAction('allocateFromWarehouse', ['warehouse_product_id' => $product->id, 'note' => 'تحویل'])
            ->assertHasNoActionErrors();

        $this->assertSame(2, $product->refresh()->quantity);
        $this->assertSame(1, $request->inventoryAllocations()->count());
    }

    public function test_warehouse_manager_panel_flow(): void
    {
        $manager = $this->staff('warehouse_manager');
        $this->actingAs($manager);

        Livewire::test(CreateWarehouse::class)
            ->fillForm(['name' => 'انبار مرکزی', 'code' => 'central', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();
        $warehouse = Warehouse::query()->where('code', 'central')->firstOrFail();

        Livewire::test(CreateWarehouseProduct::class)
            ->fillForm(['warehouse_id' => $warehouse->id, 'name' => 'بشقاب مدل X', 'code' => 'PLT-X', 'quantity' => 10])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateWarehouseProduct::class)
            ->fillForm(['warehouse_id' => $warehouse->id, 'name' => 'تکراری', 'code' => 'PLT-X', 'quantity' => 1])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        $product = WarehouseProduct::query()->where('code', 'PLT-X')->firstOrFail();
        $other = WarehouseProduct::factory()->for($warehouse)->create(['name' => 'لیوان', 'code' => 'CUP-1']);
        $request = ServiceRequest::factory()->create();

        $this->get(route('filament.admin.resources.warehouses.view', $warehouse))->assertOk();

        Livewire::test(ProductsRelationManager::class, ['ownerRecord' => $warehouse, 'pageClass' => ViewWarehouse::class])
            ->assertCanSeeTableRecords([$product, $other])
            ->searchTable('PLT')
            ->assertCanSeeTableRecords([$product])
            ->assertCanNotSeeTableRecords([$other])
            ->searchTable('لیوان')
            ->assertCanSeeTableRecords([$other])
            ->assertCanNotSeeTableRecords([$product])
            ->searchTable(null)
            ->callTableAction('allocateToRequest', $product, ['service_request_id' => $request->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame(9, $product->refresh()->quantity);
        $this->assertSame($manager->id, InventoryAllocation::query()->firstOrFail()->allocated_by);

        Livewire::test(ListWarehouses::class)->assertOk();
        Livewire::test(ListWarehouseProducts::class)->assertCanSeeTableRecords([$product, $other]);
        Livewire::test(ListInventoryAllocations::class)
            ->assertSee($request->tracking_code)
            ->callTableAction('cancelAllocation', InventoryAllocation::query()->firstOrFail(), ['reason' => 'اشتباه'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(10, $product->refresh()->quantity);
    }
}
