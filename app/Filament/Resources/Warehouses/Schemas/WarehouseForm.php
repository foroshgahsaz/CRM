<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام انبار')->required()->maxLength(120),
            TextInput::make('code')->label('کد انبار')->required()->alphaDash()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('location')->label('محل')->maxLength(255),
            Toggle::make('is_active')->label('فعال')->default(true),
            Textarea::make('description')->label('توضیح')->maxLength(1000)->columnSpanFull(),
        ]);
    }
}
