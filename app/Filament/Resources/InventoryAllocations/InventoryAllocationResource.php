<?php

namespace App\Filament\Resources\InventoryAllocations;

use App\Filament\Resources\InventoryAllocations\Pages\ListInventoryAllocations;
use App\Filament\Resources\InventoryAllocations\Tables\InventoryAllocationsTable;
use App\Models\InventoryAllocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InventoryAllocationResource extends Resource
{
    protected static ?string $model = InventoryAllocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'ارجاع‌های انبار';

    protected static ?string $modelLabel = 'ارجاع انبار';

    protected static ?string $pluralModelLabel = 'ارجاع‌های انبار';

    protected static string|UnitEnum|null $navigationGroup = 'انبار';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return InventoryAllocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryAllocations::route('/'),
        ];
    }
}
