<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\AuditLogService;
use App\Support\MobileNumber;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalize($data);
    }

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->record('user.created', $this->record, 'success', [
            'roles' => $this->record->roles()->pluck('name')->all(),
            'is_active' => $this->record->is_active,
        ], auth()->user());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $mobile = trim((string) ($data['mobile'] ?? ''));

        if ($mobile === '') {
            $data['mobile'] = null;

            return $data;
        }

        $normalized = MobileNumber::normalize($mobile);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل معتبر نیست.',
            ]);
        }

        $data['mobile'] = $normalized;

        return $data;
    }
}
