<?php

namespace App\Filament\Resources\SlaPolicies\Tables;

use App\Enums\RequestPriority;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SlaPoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('عنوان')->searchable(),
                TextColumn::make('requestType.name')->label('نوع')->placeholder('همه'),
                TextColumn::make('priority')->label('اولویت')->formatStateUsing(fn (?RequestPriority $state): string => $state?->label() ?? 'همه'),
                TextColumn::make('organizationalUnit.name')->label('واحد')->placeholder('همه'),
                TextColumn::make('response_minutes')->label('پاسخ'),
                TextColumn::make('resolution_minutes')->label('خاتمه'),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
