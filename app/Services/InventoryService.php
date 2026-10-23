<?php

namespace App\Services;

use App\Events\InventoryAllocated;
use App\Exceptions\WorkflowException;
use App\Models\InventoryAllocation;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InventoryService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Allocates exactly one unit of the product to the request.
     */
    public function allocate(WarehouseProduct $product, ServiceRequest $request, User $actor, ?string $note = null): InventoryAllocation
    {
        Gate::forUser($actor)->authorize('allocate', $product);

        return DB::transaction(function () use ($product, $request, $actor, $note): InventoryAllocation {
            $lockedProduct = WarehouseProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $lockedRequest = ServiceRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status->isTerminal()) {
                throw new WorkflowException('ارجاع کالا به درخواست بسته یا بایگانی‌شده مجاز نیست.');
            }

            if ($lockedProduct->quantity < 1) {
                throw new WorkflowException('موجودی این کالا تمام شده است.');
            }

            $warehouse = $lockedProduct->warehouse()->firstOrFail();

            if (! $warehouse->is_active) {
                throw new WorkflowException('انبار این کالا غیرفعال است.');
            }

            $lockedProduct->decrement('quantity');

            $allocation = InventoryAllocation::query()->create([
                'warehouse_product_id' => $lockedProduct->id,
                'service_request_id' => $lockedRequest->id,
                'product_name_snapshot' => $lockedProduct->name,
                'product_code_snapshot' => $lockedProduct->code,
                'warehouse_name_snapshot' => $warehouse->name,
                'allocated_by' => $actor->id,
                'note' => filled($note) ? trim((string) $note) : null,
            ]);

            $this->audit->record('inventory.allocated', $allocation, 'success', [
                'product_id' => $lockedProduct->id,
                'request_id' => $lockedRequest->id,
                'remaining' => $lockedProduct->quantity,
            ], $actor);

            event(new InventoryAllocated($lockedRequest->id, $allocation->id));

            return $allocation;
        });
    }

    /**
     * Cancels an allocation and returns its unit to stock.
     */
    public function cancel(InventoryAllocation $allocation, User $actor, string $reason): InventoryAllocation
    {
        Gate::forUser($actor)->authorize('cancel', $allocation);

        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw new WorkflowException('دلیل لغو ارجاع الزامی است.');
        }

        return DB::transaction(function () use ($allocation, $actor, $reason): InventoryAllocation {
            $locked = InventoryAllocation::query()->whereKey($allocation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw new WorkflowException('این ارجاع قبلاً لغو شده است.');
            }

            WarehouseProduct::query()->whereKey($locked->warehouse_product_id)->lockForUpdate()->firstOrFail()->increment('quantity');

            $locked->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ])->save();

            $this->audit->record('inventory.allocation_cancelled', $locked, 'success', [
                'product_id' => $locked->warehouse_product_id,
                'request_id' => $locked->service_request_id,
            ], $actor);

            return $locked;
        });
    }
}
