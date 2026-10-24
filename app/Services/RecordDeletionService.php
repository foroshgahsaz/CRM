<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalDecision;
use App\Models\AuditLog;
use App\Models\InventoryAllocation;
use App\Models\OrganizationalUnit;
use App\Models\RequestAssignment;
use App\Models\RequestAttachment;
use App\Models\RequestMessage;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permanent deletion for every admin resource. Dependents are removed or
 * detached here so foreign keys and workflow history stay consistent.
 */
class RecordDeletionService
{
    public function __construct(private AuditLogService $audit) {}

    public function delete(Model $record, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $record);

        $metadata = $this->describe($record);
        $files = [];

        DB::transaction(function () use ($record, $actor, &$files): void {
            match (true) {
                $record instanceof ServiceRequest => $files = $this->deleteServiceRequest($record),
                $record instanceof User => $this->deleteUser($record, $actor),
                $record instanceof Warehouse => $this->deleteWarehouse($record),
                $record instanceof WarehouseProduct => $this->deleteProduct($record),
                $record instanceof InventoryAllocation => $this->deleteAllocation($record),
                $record instanceof RequestType => $this->deleteRequestType($record),
                $record instanceof OrganizationalUnit => $this->deleteUnit($record),
                $record instanceof Role => $this->deleteRole($record),
                default => $record->delete(),
            };
        });

        foreach ($files as $path) {
            Storage::disk('local')->delete($path);
        }

        $this->audit->record('record.deleted', $record, 'success', $metadata, $actor);
    }

    /**
     * Human-readable warning shown in the confirmation modal.
     */
    public function warning(Model $record): string
    {
        $base = 'این عملیات قابل بازگشت نیست.';

        return match (true) {
            $record instanceof ServiceRequest => $base.' همهٔ پیام‌ها، مدارک، ارجاع‌ها و تاریخچهٔ این درخواست حذف می‌شود'
                .($record->inventoryAllocations()->whereNull('cancelled_at')->exists() ? ' و کالاهای ارجاع‌شده به موجودی انبار برمی‌گردد.' : '.'),
            $record instanceof User => $base.' نام این کاربر از سوابق پرونده‌ها برداشته می‌شود؛ مشخصات او در سوابق حسابرسی می‌ماند.',
            $record instanceof Warehouse => $base.' '.$record->products()->count().' کالای این انبار و سابقهٔ ارجاع آن‌ها هم حذف می‌شود.',
            $record instanceof WarehouseProduct => $base.' '.$record->allocations()->count().' سابقهٔ ارجاع این کالا هم حذف می‌شود.',
            $record instanceof InventoryAllocation => $base.($record->isActive() ? ' یک عدد به موجودی انبار برمی‌گردد.' : ''),
            $record instanceof Role => $base.' این نقش از '.$record->users()->count().' کاربر گرفته می‌شود.',
            default => $base,
        };
    }

    /**
     * @return list<string> attachment paths to remove after commit
     */
    private function deleteServiceRequest(ServiceRequest $request): array
    {
        $locked = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

        InventoryAllocation::query()->where('service_request_id', $locked->id)->get()
            ->each(fn (InventoryAllocation $allocation) => $this->deleteAllocation($allocation));

        $files = RequestAttachment::query()->where('service_request_id', $locked->id)->pluck('storage_path')->filter()->values()->all();

        $this->deleteRequestNotifications($locked->id);

        RequestAssignment::query()->where('service_request_id', $locked->id)->update(['parent_assignment_id' => null]);
        RequestMessage::query()->where('service_request_id', $locked->id)->update(['corrects_message_id' => null]);

        ServiceRequest::query()->whereKey($locked->id)->delete();

        return $files;
    }

    private function deleteRequestNotifications(int $requestId): void
    {
        $suffix = '/admin/service-requests/'.$requestId;

        $ids = DB::table('notifications')
            ->where('data', 'like', '%service-requests%'.$requestId.'%')
            ->get(['id', 'data'])
            ->filter(fn (object $row): bool => str_contains(str_replace('\\/', '/', (string) $row->data), $suffix.'"'))
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('notifications')->whereIn('id', $ids)->delete();
        }
    }

    private function deleteUser(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new WorkflowException('حذف حساب کاربری خودتان مجاز نیست.');
        }

        if ($user->isLastActiveSuperAdmin()) {
            throw new WorkflowException('آخرین مدیر ارشد فعال قابل حذف نیست.');
        }

        $openOwned = ServiceRequest::query()->open()->where('current_owner_id', $user->id)->count();
        $activeAssignments = RequestAssignment::query()->where('to_user_id', $user->id)->where('status', AssignmentStatus::Active->value)->count();

        if ($openOwned > 0 || $activeAssignments > 0) {
            throw new WorkflowException('این کاربر مسئول '.max($openOwned, $activeAssignments).' پرونده باز است. ابتدا پرونده‌ها را به کارشناس دیگری ارجاع دهید.');
        }

        ServiceRequest::query()->where('current_owner_id', $user->id)->update(['current_owner_id' => null]);
        ServiceRequest::query()->where('approved_by', $user->id)->update(['approved_by' => null]);
        RequestAssignment::query()->where('from_user_id', $user->id)->update(['from_user_id' => null]);
        RequestAssignment::query()->where('to_user_id', $user->id)->update(['to_user_id' => null]);
        RequestMessage::query()->where('sender_id', $user->id)->update(['sender_id' => null]);
        ApprovalDecision::query()->where('decided_by', $user->id)->update(['decided_by' => null]);

        DB::table('notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->roles()->detach();
        $user->permissions()->detach();
        $user->forceDelete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function deleteWarehouse(Warehouse $warehouse): void
    {
        WarehouseProduct::query()->where('warehouse_id', $warehouse->id)->get()
            ->each(fn (WarehouseProduct $product) => $this->deleteProduct($product));

        $warehouse->delete();
    }

    private function deleteProduct(WarehouseProduct $product): void
    {
        InventoryAllocation::query()->where('warehouse_product_id', $product->id)->delete();
        WarehouseProduct::query()->whereKey($product->id)->delete();
    }

    private function deleteAllocation(InventoryAllocation $allocation): void
    {
        $locked = InventoryAllocation::query()->whereKey($allocation->id)->lockForUpdate()->firstOrFail();

        if ($locked->isActive()) {
            WarehouseProduct::query()->whereKey($locked->warehouse_product_id)->increment('quantity');
        }

        $locked->delete();
    }

    private function deleteRequestType(RequestType $type): void
    {
        $count = $type->serviceRequests()->count();

        if ($count > 0) {
            throw new WorkflowException($count.' درخواست از این نوع وجود دارد. ابتدا آن درخواست‌ها را حذف کنید یا این نوع را غیرفعال کنید.');
        }

        $type->delete();
    }

    private function deleteUnit(OrganizationalUnit $unit): void
    {
        $waiting = ServiceRequest::query()
            ->where('current_unit_id', $unit->id)
            ->where('status', RequestStatus::AwaitingUnitClaim->value)
            ->count();

        if ($waiting > 0) {
            throw new WorkflowException($waiting.' پرونده در انتظار پذیرش این واحد است. ابتدا آن‌ها را به واحد یا کارشناس دیگری ارجاع دهید.');
        }

        $unit->delete();
    }

    private function deleteRole(Role $role): void
    {
        if ($role->name === 'super_admin') {
            throw new WorkflowException('نقش مدیر ارشد سامانه قابل حذف نیست.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(Model $record): array
    {
        return array_filter([
            'type' => class_basename($record),
            'id' => $record->getKey(),
            'label' => match (true) {
                $record instanceof ServiceRequest => $record->tracking_code,
                $record instanceof User => $record->name.' <'.$record->email.'>',
                $record instanceof AuditLog => $record->event,
                default => $record->getAttribute('name') ?? $record->getAttribute('title') ?? $record->getAttribute('event_key'),
            },
            'mobile' => $record instanceof User ? $record->mobile : null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
