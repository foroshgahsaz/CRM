<?php

namespace App\Filament\Resources\Roles\Concerns;

use App\Support\PermissionCatalog;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;

trait InteractsWithGroupedRolePermissions
{
    /** @var list<int>|null */
    protected ?array $pendingPermissionIds = null;

    /**
     * @return list<Section>
     */
    protected static function groupedPermissionSections(): array
    {
        $sections = [];

        foreach (PermissionCatalog::permissionGroups() as $groupKey => $group) {
            $sections[] = Section::make($group['label'])
                ->description($group['description'] ?? null)
                ->compact()
                ->extraAttributes(['class' => 'crm-role-permission-group'])
                ->schema([
                    CheckboxList::make(self::permissionFieldKey($groupKey))
                        ->hiddenLabel()
                        ->columns(2)
                        ->bulkToggleable()
                        ->options(fn (): array => Permission::query()
                            ->whereIn('name', $group['permissions'])
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Permission $permission): array => [
                                $permission->id => PermissionCatalog::permissions()[$permission->name] ?? $permission->name,
                            ])
                            ->all())
                        ->disabled(fn ($livewire): bool => $livewire->record?->name === 'super_admin')
                        ->helperText(fn ($livewire): ?string => $livewire->record?->name === 'super_admin'
                            ? 'مدیر ارشد همیشه همهٔ مجوزها را دارد.'
                            : null),
                ]);
        }

        return $sections;
    }

    protected static function permissionFieldKey(string $groupKey): string
    {
        return 'permission_group_'.$groupKey;
    }

    /**
     * @return list<string>
     */
    protected static function permissionFieldKeys(): array
    {
        return array_map(
            fn (string $groupKey): string => self::permissionFieldKey($groupKey),
            array_keys(PermissionCatalog::permissionGroups()),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    protected function collectPermissionIdsFromFormData(array $data): array
    {
        $ids = [];

        foreach (self::permissionFieldKeys() as $field) {
            $groupIds = $data[$field] ?? [];
            if (is_array($groupIds)) {
                foreach ($groupIds as $id) {
                    if (is_numeric($id)) {
                        $ids[] = (int) $id;
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        if (! isset($this->record)) {
            return $data;
        }

        $assigned = $this->record->permissions()->pluck('permissions.id')->all();

        foreach (PermissionCatalog::permissionGroups() as $groupKey => $group) {
            $data[self::permissionFieldKey($groupKey)] = Permission::query()
                ->whereIn('name', $group['permissions'])
                ->whereIn('id', $assigned)
                ->pluck('id')
                ->all();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripPermissionGroupsFromFormData(array $data): array
    {
        if (($this->record->name ?? null) !== 'super_admin') {
            $this->pendingPermissionIds = $this->collectPermissionIdsFromFormData($data);
        }

        return Arr::except($data, self::permissionFieldKeys());
    }

    protected function syncPendingPermissions(): void
    {
        if ($this->record->name === 'super_admin') {
            $this->record->syncPermissions(array_keys(PermissionCatalog::permissions()));

            return;
        }

        if ($this->pendingPermissionIds !== null) {
            $this->record->syncPermissions($this->pendingPermissionIds);
        }
    }
}
