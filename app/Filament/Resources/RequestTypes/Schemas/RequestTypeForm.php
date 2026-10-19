<?php

namespace App\Filament\Resources\RequestTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RequestTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('عنوان')->required()->maxLength(120),
            TextInput::make('slug')->label('شناسه')->required()->alphaDash()->maxLength(60)->unique(ignoreRecord: true),
            Textarea::make('description')->label('توضیح')->maxLength(1000)->columnSpanFull(),
            TextInput::make('sort_order')->label('ترتیب')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }
}
