<?php

namespace App\Models;

use Database\Factories\InventoryAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'warehouse_product_id',
    'service_request_id',
    'product_name_snapshot',
    'product_code_snapshot',
    'warehouse_name_snapshot',
    'allocated_by',
    'note',
    'cancelled_at',
    'cancelled_by',
    'cancel_reason',
])]
class InventoryAllocation extends Model
{
    /** @use HasFactory<InventoryAllocationFactory> */
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(WarehouseProduct::class, 'warehouse_product_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isActive(): bool
    {
        return $this->cancelled_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }
}
