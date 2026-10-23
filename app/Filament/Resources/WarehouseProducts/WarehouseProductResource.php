<?php

namespace App\Filament\Resources\WarehouseProducts;

use App\Filament\Resources\WarehouseProducts\Pages\CreateWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Pages\EditWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Pages\ListWarehouseProducts;
use App\Filament\Resources\WarehouseProducts\RelationManagers\AllocationsRelationManager;
use App\Filament\Resources\WarehouseProducts\Schemas\WarehouseProductForm;
use App\Filament\Resources\WarehouseProducts\Tables\WarehouseProductsTable;
use App\Models\WarehouseProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseProductResource extends Resource
{
    protected static ?string $model = WarehouseProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'موجودی انبار';

    protected static ?string $modelLabel = 'کالا';

    protected static ?string $pluralModelLabel = 'موجودی انبار';

    protected static string|UnitEnum|null $navigationGroup = 'انبار';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WarehouseProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseProducts::route('/'),
            'create' => CreateWarehouseProduct::route('/create'),
            'edit' => EditWarehouseProduct::route('/{record}/edit'),
        ];
    }
}
