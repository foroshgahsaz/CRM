<?php

namespace App\Filament\Resources\SmsLogs;

use App\Filament\Resources\SmsLogs\Pages\ListSmsLogs;
use App\Filament\Resources\SmsLogs\Tables\SmsLogsTable;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SmsLogResource extends Resource
{
    protected static ?string $model = SmsLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'گزارش پیامک';

    protected static ?string $modelLabel = 'پیامک';

    protected static ?string $pluralModelLabel = 'پیامک‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'نظارت';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return SmsLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsLogs::route('/'),
        ];
    }
}
