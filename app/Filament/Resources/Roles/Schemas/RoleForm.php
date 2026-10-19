<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\PermissionCatalog;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('شناسه نقش')->required()->alphaDash()->maxLength(60)
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Role $record): bool => $record !== null && array_key_exists($record->name, PermissionCatalog::roles()))
                ->dehydrated(fn (?Role $record): bool => $record === null || ! array_key_exists($record->name, PermissionCatalog::roles()))
                ->helperText('نقش‌های پایه سامانه قابل تغییر نام نیستند.'),
            TextInput::make('guard_name')->default('web')->disabled()->dehydrated(false),
            CheckboxList::make('permissions')
                ->label('مجوزها')
                ->columns(2)
                ->bulkToggleable()
                ->searchable()
                ->relationship('permissions', 'name')
                ->options(fn () => Permission::query()->orderBy('name')->pluck('name', 'id')->map(
                    fn (string $name): string => PermissionCatalog::permissions()[$name] ?? $name,
                )->all())
                ->disabled(fn (?Role $record): bool => $record?->name === 'super_admin')
                ->helperText('نقش مدیر ارشد همیشه همه مجوزها را دارد.'),
        ]);
    }
}
