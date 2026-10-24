<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Filament\Actions\DeleteRecordAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_key')->label('رویداد')->searchable(),
                TextColumn::make('title')->label('عنوان')->searchable(),
                TextColumn::make('channel')->label('کانال'),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteRecordAction::make(),
            ])
            ->toolbarActions([
                DeleteRecordAction::bulkGroup(),
            ]);
    }
}
