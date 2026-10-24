<?php

namespace App\Filament\Resources\WarehouseProducts\Tables;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Actions\InventoryActions;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Services\AuditLogService;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehouseProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('warehouse')->withCount('activeAllocations'))
            ->columns(self::columns(withWarehouse: true))
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('انبار')
                    ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                TernaryFilter::make('in_stock')
                    ->label('موجودی')
                    ->trueLabel('دارای موجودی')
                    ->falseLabel('تمام‌شده')
                    ->queries(
                        true: fn (Builder $query) => $query->where('quantity', '>', 0),
                        false: fn (Builder $query) => $query->where('quantity', 0),
                    ),
            ])
            ->recordActions([
                InventoryActions::allocateToRequest(),
                EditAction::make(),
                DeleteRecordAction::make(),
            ])
            ->toolbarActions([
                DeleteRecordAction::bulkGroup(),
            ]);
    }

    /**
     * @return list<Column>
     */
    public static function columns(bool $withWarehouse): array
    {
        return array_values(array_filter([
            TextColumn::make('code')->label('کد کالا')->searchable()->sortable()->copyable(),
            TextColumn::make('name')->label('نام کالا')->searchable()->sortable(),
            $withWarehouse ? TextColumn::make('warehouse.name')->label('انبار')->sortable() : null,
            TextColumn::make('quantity')
                ->label('موجودی')
                ->sortable()
                ->badge()
                ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                ->formatStateUsing(fn (int $state): string => $state > 0 ? $state.' عدد' : 'تمام شد'),
            TextColumn::make('active_allocations_count')->label('ارجاع‌شده')->suffix(' عدد'),
        ]));
    }

    public static function auditUpdate(WarehouseProduct $record): void
    {
        app(AuditLogService::class)->record('inventory.product_updated', $record, 'success', [
            'warehouse_id' => $record->warehouse_id,
            'quantity_before' => $record->getPrevious()['quantity'] ?? $record->quantity,
            'quantity_after' => $record->quantity,
        ], auth()->user());
    }
}
