<?php

namespace App\Filament\Resources\WarehouseProducts\Pages;

use App\Filament\Resources\WarehouseProducts\WarehouseProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseProducts extends ListRecords
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('افزودن موجودی'),
        ];
    }
}
