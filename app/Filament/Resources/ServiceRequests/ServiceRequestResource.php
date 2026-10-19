<?php

namespace App\Filament\Resources\ServiceRequests;

use App\Filament\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use App\Filament\Resources\ServiceRequests\Schemas\ServiceRequestInfolist;
use App\Filament\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'درخواست‌ها';

    protected static ?string $modelLabel = 'درخواست';

    protected static ?string $pluralModelLabel = 'درخواست‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'عملیات';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['customer', 'requestType', 'currentOwner', 'currentUnit', 'currentAssignment']);
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('0 = 1');
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequests::route('/'),
            'create' => CreateServiceRequest::route('/create'),
            'view' => ViewServiceRequest::route('/{record}'),
        ];
    }
}
