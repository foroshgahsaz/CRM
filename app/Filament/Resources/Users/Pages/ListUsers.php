<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\BulkImportUsers;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('bulkImport')
                ->label('ورود گروهی (اکسل)')
                ->url(BulkImportUsers::getUrl())
                ->visible(fn (): bool => auth()->user()?->can('users.manage') ?? false),
        ];
    }
}
