<?php

namespace App\Filament\Resources\InventoryAllocations\Tables;

use App\Filament\Actions\InventoryActions;
use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\InventoryAllocation;
use App\Support\JalaliDate;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryAllocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['serviceRequest', 'allocator', 'canceller']))
            ->columns(self::columns(withProduct: true))
            ->filters([
                TernaryFilter::make('active')
                    ->label('وضعیت')
                    ->trueLabel('فعال')
                    ->falseLabel('لغوشده')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('cancelled_at'),
                        false: fn (Builder $query) => $query->whereNotNull('cancelled_at'),
                    ),
            ])
            ->recordActions([
                InventoryActions::cancelAllocation(),
            ]);
    }

    /**
     * @return list<Column>
     */
    public static function columns(bool $withProduct): array
    {
        return array_values(array_filter([
            TextColumn::make('serviceRequest.tracking_code')
                ->label('درخواست')
                ->searchable()
                ->url(fn (InventoryAllocation $record): ?string => auth()->user()?->can('view', $record->serviceRequest)
                    ? ServiceRequestResource::getUrl('view', ['record' => $record->service_request_id])
                    : null),
            $withProduct ? TextColumn::make('product_name_snapshot')->label('کالا')->searchable() : null,
            $withProduct ? TextColumn::make('product_code_snapshot')->label('کد کالا')->searchable() : null,
            $withProduct ? TextColumn::make('warehouse_name_snapshot')->label('انبار') : null,
            TextColumn::make('allocator.name')->label('ارجاع‌دهنده')->placeholder('—'),
            TextColumn::make('created_at')->label('زمان ارجاع')->formatStateUsing(fn ($state): string => JalaliDate::format($state))->sortable(),
            TextColumn::make('note')->label('توضیح')->placeholder('—')->limit(40),
            TextColumn::make('status')
                ->label('وضعیت')
                ->state(fn (InventoryAllocation $record): string => $record->isActive() ? 'فعال' : 'لغوشده')
                ->badge()
                ->color(fn (string $state): string => $state === 'فعال' ? 'success' : 'gray')
                ->tooltip(fn (InventoryAllocation $record): ?string => $record->isActive()
                    ? null
                    : 'لغو توسط '.($record->canceller?->name ?? '—').' در '.JalaliDate::format($record->cancelled_at).': '.$record->cancel_reason),
        ]));
    }
}
