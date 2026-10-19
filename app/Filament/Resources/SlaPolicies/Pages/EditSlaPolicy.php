<?php

namespace App\Filament\Resources\SlaPolicies\Pages;

use App\Filament\Resources\SlaPolicies\SlaPolicyResource;
use App\Services\AuditLogService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlaPolicy extends EditRecord
{
    protected static string $resource = SlaPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->record('sla.updated', $this->record, 'success', [
            'name' => $this->record->name,
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }
}
