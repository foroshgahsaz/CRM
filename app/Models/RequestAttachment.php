<?php

namespace App\Models;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use Database\Factories\RequestAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'service_request_id',
    'assignment_id',
    'uploaded_by_user_id',
    'customer_id',
    'category',
    'original_name',
    'storage_path',
    'mime_type',
    'size',
    'visibility',
    'checksum',
])]
class RequestAttachment extends Model
{
    /** @use HasFactory<RequestAttachmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (RequestAttachment $attachment): void {
            Storage::disk('local')->delete($attachment->storage_path);
        });
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AttachmentCategory::class,
            'visibility' => AttachmentVisibility::class,
        ];
    }
}
