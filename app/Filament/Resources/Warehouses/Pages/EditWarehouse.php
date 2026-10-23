<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Services\AuditLogService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->record('warehouse.updated', $this->record, 'success', [
            'code' => $this->record->code,
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }
}
