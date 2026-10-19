<?php

namespace App\Filament\Resources\RequestTypes\Pages;

use App\Filament\Resources\RequestTypes\RequestTypeResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestType extends CreateRecord
{
    protected static string $resource = RequestTypeResource::class;

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->record('request_type.created', $this->record, 'success', [
            'slug' => $this->record->slug,
        ], auth()->user());
    }
}
