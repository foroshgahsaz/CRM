<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Filament\Actions\DeleteRecordAction;
use App\Support\JalaliDate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('زمان')->formatStateUsing(fn ($state): string => JalaliDate::format($state)),
                TextColumn::make('event')->label('رویداد')->searchable(),
                TextColumn::make('user.name')->label('عامل')->placeholder('سامانه'),
                TextColumn::make('result')->label('نتیجه')->badge(),
                TextColumn::make('auditable_type')->label('موضوع')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('auditable_id')->label('شناسه')->placeholder('—'),
                TextColumn::make('ip_address')->label('IP')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('result')->label('نتیجه')->options([
                    'success' => 'success',
                    'failure' => 'failure',
                ]),
            ])
            ->recordActions([
                DeleteRecordAction::make(),
            ])
            ->toolbarActions([
                DeleteRecordAction::bulkGroup(),
            ]);
    }
}
