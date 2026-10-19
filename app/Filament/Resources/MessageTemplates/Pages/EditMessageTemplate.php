<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditMessageTemplate extends EditRecord
{
    protected static string $resource = MessageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->record('sms.template_updated', $this->record, 'success', [
            'event_key' => $this->record->event_key,
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }
}
