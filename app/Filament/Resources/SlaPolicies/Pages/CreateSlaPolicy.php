<?php

namespace App\Filament\Resources\SlaPolicies\Pages;

use App\Filament\Resources\SlaPolicies\SlaPolicyResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateSlaPolicy extends CreateRecord
{
    protected static string $resource = SlaPolicyResource::class;

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->record('sla.created', $this->record, 'success', [
            'name' => $this->record->name,
        ], auth()->user());
    }
}
