<?php

namespace App\Filament\Resources\RequestTypes;

use App\Filament\Resources\RequestTypes\Pages\CreateRequestType;
use App\Filament\Resources\RequestTypes\Pages\EditRequestType;
use App\Filament\Resources\RequestTypes\Pages\ListRequestTypes;
use App\Filament\Resources\RequestTypes\RelationManagers\FieldsRelationManager;
use App\Filament\Resources\RequestTypes\Schemas\RequestTypeForm;
use App\Filament\Resources\RequestTypes\Tables\RequestTypesTable;
use App\Models\RequestType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RequestTypeResource extends Resource
{
    protected static ?string $model = RequestType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'انواع درخواست';

    protected static ?string $modelLabel = 'نوع درخواست';

    protected static ?string $pluralModelLabel = 'انواع درخواست';

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RequestTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FieldsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequestTypes::route('/'),
            'create' => CreateRequestType::route('/create'),
            'edit' => EditRequestType::route('/{record}/edit'),
        ];
    }
}
