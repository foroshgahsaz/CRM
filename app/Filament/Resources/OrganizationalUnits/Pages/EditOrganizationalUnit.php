<?php

namespace App\Filament\Resources\OrganizationalUnits\Pages;

use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Services\AuditLogService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationalUnit extends EditRecord
{
    protected static string $resource = OrganizationalUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->record('unit.updated', $this->record, 'success', [
            'code' => $this->record->code,
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }
}
