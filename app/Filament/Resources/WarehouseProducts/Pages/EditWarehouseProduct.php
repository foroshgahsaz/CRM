<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Resources\WarehouseProducts\Tables\WarehouseProductsTable;
use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseProduct extends EditRecord
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteRecordAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        WarehouseProductsTable::auditUpdate($this->record);
    }
}
