<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Actions\DeleteRecordAction;
use App\Support\PermissionCatalog;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نقش')->formatStateUsing(fn (string $state): string => PermissionCatalog::roles()[$state] ?? $state)->description(fn (string $state): string => $state),
                TextColumn::make('permissions_count')->counts('permissions')->label('مجوزها'),
                TextColumn::make('users_count')->counts('users')->label('کاربران'),
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
