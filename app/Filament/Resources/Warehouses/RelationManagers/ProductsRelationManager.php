<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Actions\InventoryActions;
use App\Filament\Resources\WarehouseProducts\Schemas\WarehouseProductForm;
use App\Filament\Resources\WarehouseProducts\Tables\WarehouseProductsTable;
use App\Models\WarehouseProduct;
use App\Services\AuditLogService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'کالاهای موجود در انبار';

    protected static ?string $modelLabel = 'کالا';

    protected static ?string $pluralModelLabel = 'کالاها';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(WarehouseProductForm::fields(fn (): int => $this->getOwnerRecord()->getKey()));
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('activeAllocations'))
            ->columns(WarehouseProductsTable::columns(withWarehouse: false))
            ->headerActions([
                CreateAction::make()
                    ->after(fn (WarehouseProduct $record) => app(AuditLogService::class)->record('inventory.product_created', $record, 'success', [
                        'warehouse_id' => $record->warehouse_id,
                        'quantity' => $record->quantity,
                    ], auth()->user())),
            ])
            ->recordActions([
                InventoryActions::allocateToRequest(),
                EditAction::make()
                    ->after(fn (WarehouseProduct $record) => WarehouseProductsTable::auditUpdate($record)),
                DeleteRecordAction::make(),
            ]);
    }
}
