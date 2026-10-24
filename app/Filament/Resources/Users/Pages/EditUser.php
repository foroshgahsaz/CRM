<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Resources\Users\UserResource;
use App\Services\AuditLogService;
use App\Support\MobileNumber;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
        $mobile = trim((string) ($data['mobile'] ?? ''));

        if ($mobile === '') {
            $data['mobile'] = null;
        } else {
            $normalized = MobileNumber::normalize($mobile);

            if ($normalized === null) {
                throw ValidationException::withMessages([
                    'data.mobile' => 'شماره موبایل معتبر نیست.',
                ]);
            }

            $data['mobile'] = $normalized;
        }

        $roleIds = collect($this->data['roles'] ?? []);
        $keepsSuperAdmin = Role::query()->whereIn('id', $roleIds)->where('name', 'super_admin')->exists();
        $staysActive = (bool) ($data['is_active'] ?? false);

        if ($this->getRecord()->isLastActiveSuperAdmin() && (! $keepsSuperAdmin || ! $staysActive)) {
            throw ValidationException::withMessages([
                'data.roles' => 'آخرین مدیر ارشد فعال را نمی‌توان غیرفعال کرد یا نقش او را برداشت.',
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->record('user.updated', $this->record, 'success', [
            'roles' => $this->record->roles()->pluck('name')->all(),
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }
}
