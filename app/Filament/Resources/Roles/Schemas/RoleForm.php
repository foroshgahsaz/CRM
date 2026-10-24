<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Resources\Roles\Concerns\InteractsWithGroupedRolePermissions;
use App\Support\PermissionCatalog;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    use InteractsWithGroupedRolePermissions;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('شناسه نقش')
                ->description('نام فنی نقش در سامانه. نقش‌های پایه قابل تغییر نام نیستند.')
                ->schema([
                    TextInput::make('name')
                        ->label('شناسه نقش')
                        ->required()
                        ->alphaDash()
                        ->maxLength(60)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record !== null && array_key_exists($record->name, PermissionCatalog::roles()))
                        ->dehydrated(fn (?Role $record): bool => $record === null || ! array_key_exists($record->name, PermissionCatalog::roles()))
                        ->helperText(fn (?Role $record): ?string => $record !== null && array_key_exists($record->name, PermissionCatalog::roles())
                            ? 'عنوان فارسی: '.(PermissionCatalog::roles()[$record->name] ?? $record->name)
                            : 'نقش‌های پایه سامانه قابل تغییر نام نیستند.'),
                    TextInput::make('guard_name')->default('web')->disabled()->dehydrated(false),
                ])
                ->columns(2)
                ->extraAttributes(['class' => 'crm-role-identity-section']),
            Section::make('مجوزها')
                ->description('دسترسی‌های این نقش را در هر دسته جداگانه انتخاب کنید.')
                ->schema(self::groupedPermissionSections())
                ->extraAttributes(['class' => 'crm-role-permissions-section']),
        ]);
    }
}
