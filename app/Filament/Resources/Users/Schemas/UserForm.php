<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\PermissionCatalog;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('حساب')->schema([
                TextInput::make('name')->label('نام')->required()->maxLength(120),
                TextInput::make('email')->label('ایمیل')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
                TextInput::make('mobile')->label('موبایل')->maxLength(20),
                Select::make('organizational_unit_id')->label('واحد سازمانی')->relationship('organizationalUnit', 'name')->searchable()->preload(),
                Toggle::make('is_active')->label('فعال')->default(true),
            ])->columns(2),
            Section::make('ورود')->schema([
                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(10)->mixedCase()->numbers()->symbols())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->confirmed()
                    ->helperText('حداقل ۱۰ نویسه، شامل حرف بزرگ، حرف کوچک، عدد و نماد.'),
                TextInput::make('password_confirmation')->label('تکرار رمز')->password()->revealable()->dehydrated(false),
            ])->columns(2),
            Select::make('roles')
                ->label('نقش‌ها')
                ->multiple()
                ->preload()
                ->searchable()
                ->required()
                ->relationship('roles', 'name')
                ->getOptionLabelFromRecordUsing(fn (Role $record): string => PermissionCatalog::roles()[$record->name] ?? $record->name),
        ]);
    }
}
