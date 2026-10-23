<?php

namespace App\Filament\Resources\WarehouseProducts\RelationManagers;

use App\Filament\Actions\InventoryActions;
use App\Filament\Resources\InventoryAllocations\Tables\InventoryAllocationsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'سابقه ارجاع این کالا';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['serviceRequest', 'allocator', 'canceller']))
            ->columns(InventoryAllocationsTable::columns(withProduct: false))
            ->recordActions([
                InventoryActions::cancelAllocation(),
            ]);
    }
}
