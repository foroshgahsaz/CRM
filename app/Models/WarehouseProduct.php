<?php

namespace App\Models;

use Database\Factories\WarehouseProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['warehouse_id', 'name', 'code', 'quantity', 'description'])]
class WarehouseProduct extends Model
{
    /** @use HasFactory<WarehouseProductFactory> */
    use HasFactory;

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InventoryAllocation::class)->latest('id');
    }

    public function activeAllocations(): HasMany
    {
        return $this->hasMany(InventoryAllocation::class)->whereNull('cancelled_at');
    }

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    public function displayLabel(): string
    {
        return $this->name.' ('.$this->code.')';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
