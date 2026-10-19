<?php

namespace App\Filament\Resources\SlaPolicies;

use App\Filament\Resources\SlaPolicies\Pages\CreateSlaPolicy;
use App\Filament\Resources\SlaPolicies\Pages\EditSlaPolicy;
use App\Filament\Resources\SlaPolicies\Pages\ListSlaPolicies;
use App\Filament\Resources\SlaPolicies\Schemas\SlaPolicyForm;
use App\Filament\Resources\SlaPolicies\Tables\SlaPoliciesTable;
use App\Models\SlaPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SlaPolicyResource extends Resource
{
    protected static ?string $model = SlaPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'مهلت رسیدگی';

    protected static ?string $modelLabel = 'سیاست مهلت';

    protected static ?string $pluralModelLabel = 'سیاست‌های مهلت';

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SlaPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlaPoliciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlaPolicies::route('/'),
            'create' => CreateSlaPolicy::route('/create'),
            'edit' => EditSlaPolicy::route('/{record}/edit'),
        ];
    }
}
