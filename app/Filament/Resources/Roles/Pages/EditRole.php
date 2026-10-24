<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Resources\Roles\RoleResource;
use App\Services\AuditLogService;
use App\Support\PermissionCatalog;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteRecordAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->name === 'super_admin') {
            $this->record->syncPermissions(array_keys(PermissionCatalog::permissions()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(AuditLogService::class)->record('role.updated', $this->record, 'success', [
            'name' => $this->record->name,
            'permissions' => $this->record->permissions()->pluck('name')->all(),
        ], auth()->user());
    }
}
