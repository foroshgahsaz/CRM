<?php

namespace App\Filament\Resources\ServiceRequests\Pages;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Services\RequestSubmissionService;
use App\Support\JalaliDate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected static ?string $title = 'ثبت درخواست داخلی';

    protected function handleRecordCreation(array $data): Model
    {
        $date = JalaliDate::toGregorianDate((string) ($data['purchase_jalali'] ?? ''));

        if ($date === null || $date > now()->toDateString()) {
            throw ValidationException::withMessages([
                'data.purchase_jalali' => 'تاریخ خرید معتبر نیست.',
            ]);
        }

        $files = collect($data['attachments'] ?? [])
            ->flatten()
            ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
            ->values()
            ->all();

        return app(RequestSubmissionService::class)->submitInternal(auth()->user(), [
            'full_name' => $data['full_name'],
            'mobile' => $data['mobile'],
            'request_type_id' => $data['request_type_id'],
            'purchase_date' => $date,
            'invoice_number' => $data['invoice_number'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'purchased_quantity' => $data['purchased_quantity'] ?? null,
            'affected_quantity' => $data['affected_quantity'] ?? null,
            'description' => $data['description'],
            'priority' => $data['priority'],
            'fields' => $data['fields'] ?? [],
        ], $files);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
