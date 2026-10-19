<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationalUnit extends CreateRecord
{
    protected static string $resource = OrganizationalUnitResource::class;

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->record('unit.created', $this->record, 'success', [
            'code' => $this->record->code,
        ], auth()->user());
    }
}
