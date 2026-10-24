<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Filament\Actions\DeleteRecordAction;
use App\Support\MessageTemplateCatalog;
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
                TextColumn::make('event_key')
                    ->label('رویداد')
                    ->formatStateUsing(fn (string $state): string => MessageTemplateCatalog::eventLabel($state))
                    ->searchable(),
                TextColumn::make('sms_ir_template_id')
                    ->label('آی‌دی الگو')
                    ->placeholder('—')
                    ->visible(MessageTemplateCatalog::usesSmsIrPatterns()),
                TextColumn::make('title')->label('عنوان')->searchable()->toggleable(isToggledHiddenByDefault: true),
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
