<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Concerns\InteractsWithGroupedRolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use App\Services\AuditLogService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateRole extends CreateRecord
{
    use InteractsWithGroupedRolePermissions;

    protected static string $resource = RoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data['guard_name'] = 'web';
        $this->pendingPermissionIds = $this->collectPermissionIdsFromFormData($data);

        return Arr::except($data, self::permissionFieldKeys());
    }

    protected function afterCreate(): void
    {
        $this->syncPendingPermissions();

        app(AuditLogService::class)->record('role.created', $this->record, 'success', [
            'name' => $this->record->name,
            'permissions' => $this->record->permissions()->pluck('name')->all(),
        ], auth()->user());
    }
}
