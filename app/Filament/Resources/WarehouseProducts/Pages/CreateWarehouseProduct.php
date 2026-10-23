<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouseProduct extends CreateRecord
{
    protected static string $resource = WarehouseProductResource::class;

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->record('inventory.product_created', $this->record, 'success', [
            'warehouse_id' => $this->record->warehouse_id,
            'quantity' => $this->record->quantity,
        ], auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
