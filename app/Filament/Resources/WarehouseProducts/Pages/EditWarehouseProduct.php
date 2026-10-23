<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Resources\WarehouseProducts\Tables\WarehouseProductsTable;
use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseProduct extends EditRecord
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        WarehouseProductsTable::auditUpdate($this->record);
    }
}
