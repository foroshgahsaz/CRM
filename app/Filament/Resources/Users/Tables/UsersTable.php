<?php

namespace App\Filament\Resources\Users\Tables;

use App\Support\PermissionCatalog;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('email')->label('ایمیل')->searchable(),
                TextColumn::make('mobile')->label('موبایل')->placeholder('—'),
                TextColumn::make('organizationalUnit.name')->label('واحد')->placeholder('—'),
                TextColumn::make('roles.name')->label('نقش')->badge()->formatStateUsing(fn (string $state): string => PermissionCatalog::roles()[$state] ?? $state),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
