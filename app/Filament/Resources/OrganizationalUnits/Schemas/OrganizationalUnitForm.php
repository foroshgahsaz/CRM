<?php

namespace App\Filament\Resources\OrganizationalUnits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationalUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(120),
            TextInput::make('code')->label('کد')->required()->alphaDash()->maxLength(40)->unique(ignoreRecord: true),
            Select::make('parent_id')->label('واحد بالادست')->relationship('parent', 'name')->searchable()->preload(),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }
}
