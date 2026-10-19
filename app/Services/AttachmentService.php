<?php

namespace App\Services;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use App\Models\Customer;
use App\Models\RequestAttachment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function __construct(private SettingsService $settings) {}

    public function store(
        UploadedFile $file,
        ServiceRequest $request,
        AttachmentCategory $category,
        AttachmentVisibility $visibility,
        ?User $user = null,
        ?Customer $customer = null,
        ?int $assignmentId = null,
    ): RequestAttachment {
        $maxFiles = $this->settings->getInt('max_upload_files', 5);
        $maxKb = $this->settings->getInt('max_upload_kb', 5120);

        if ($request->attachments()->count() >= $maxFiles) {
            throw ValidationException::withMessages([
                'attachments' => 'تعداد فایل‌های مجاز تکمیل شده است.',
            ]);
        }

        if ($file->getSize() > ($maxKb * 1024)) {
            throw ValidationException::withMessages([
                'attachments' => 'حجم فایل بیش از حد مجاز است.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];

        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: '';

        if (! isset($allowed[$extension]) || $detected !== $allowed[$extension]) {
            throw ValidationException::withMessages([
                'attachments' => 'فایل انتخاب‌شده مجاز نیست.',
            ]);
        }

        $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        $path = 'attachments/'.$request->id.'/'.Str::uuid()->toString().'.'.$safeExtension;
        Storage::disk('local')->put($path, (string) file_get_contents($file->getRealPath()));

        return RequestAttachment::query()->create([
            'service_request_id' => $request->id,
            'assignment_id' => $assignmentId,
            'uploaded_by_user_id' => $user?->id,
            'customer_id' => $customer?->id,
            'category' => $category,
            'original_name' => $this->originalName($file->getClientOriginalName(), $safeExtension),
            'storage_path' => $path,
            'mime_type' => $detected,
            'size' => $file->getSize(),
            'visibility' => $visibility,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
        ]);
    }

    private function originalName(string $name, string $extension): string
    {
        $base = pathinfo(str_replace(['\\', '/'], '', $name), PATHINFO_FILENAME);
        $base = trim($base) === '' ? 'file' : $base;

        return Str::limit($base, 150, '').'.'.$extension;
    }
}
