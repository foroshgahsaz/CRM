<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Resources\Roles\Concerns\InteractsWithGroupedRolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Services\AuditLogService;
use App\Support\PermissionCatalog;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    use InteractsWithGroupedRolePermissions;

    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        $label = PermissionCatalog::roles()[$this->record->name] ?? $this->record->name;

        return 'ویرایش نقش: '.$label;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteRecordAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        return $this->stripPermissionGroupsFromFormData($data);
    }

    protected function afterSave(): void
    {
        $this->syncPendingPermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(AuditLogService::class)->record('role.updated', $this->record, 'success', [
            'name' => $this->record->name,
            'permissions' => $this->record->permissions()->pluck('name')->all(),
        ], auth()->user());
    }
}
