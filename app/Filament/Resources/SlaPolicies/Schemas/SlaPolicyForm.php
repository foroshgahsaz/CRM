<?php

namespace App\Filament\Resources\SlaPolicies\Schemas;

use App\Enums\RequestPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlaPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('عنوان')->required()->maxLength(120),
            Select::make('request_type_id')->label('نوع درخواست')->relationship('requestType', 'name')->searchable()->preload(),
            Select::make('priority')->label('اولویت')->options(collect(RequestPriority::cases())->mapWithKeys(fn (RequestPriority $priority) => [$priority->value => $priority->label()])),
            Select::make('organizational_unit_id')->label('واحد')->relationship('organizationalUnit', 'name')->searchable()->preload(),
            TextInput::make('response_minutes')->label('مهلت اولین پاسخ (دقیقه)')->numeric()->required()->minValue(1),
            TextInput::make('resolution_minutes')->label('مهلت خاتمه (دقیقه)')->numeric()->required()->minValue(1),
            Toggle::make('use_business_hours')->label('محاسبه با ساعت کاری')->default(true),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }
}
