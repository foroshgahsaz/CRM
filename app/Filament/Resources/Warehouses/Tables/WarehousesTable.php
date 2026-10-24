<?php

namespace App\Filament\Resources\Warehouses\Tables;

use App\Filament\Actions\DeleteRecordAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('products')->withSum('products', 'quantity'))
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('code')->label('کد')->searchable(),
                TextColumn::make('location')->label('محل')->placeholder('—'),
                TextColumn::make('products_count')->label('تعداد کالا'),
                TextColumn::make('products_sum_quantity')->label('مجموع موجودی')->placeholder('0'),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                ViewAction::make()->label('ورود به انبار'),
                EditAction::make(),
                DeleteRecordAction::make(),
            ])
            ->toolbarActions([
                DeleteRecordAction::bulkGroup(),
            ]);
    }
}
