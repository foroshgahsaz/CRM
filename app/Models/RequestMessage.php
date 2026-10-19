<?php

namespace App\Models;

use App\Enums\MessageType;
use App\Enums\MessageVisibility;
use App\Exceptions\WorkflowException;
use Database\Factories\RequestMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id',
    'assignment_id',
    'sender_id',
    'customer_id',
    'recipient_id',
    'message_type',
    'visibility',
    'body',
    'corrects_message_id',
    'is_final',
])]
class RequestMessage extends Model
{
    /** @use HasFactory<RequestMessageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (RequestMessage $message): void {
            if ($message->isDirty('body') || $message->isDirty('visibility') || $message->isDirty('message_type')) {
                throw new WorkflowException('پیام ثبت‌شده قابل تغییر نیست. برای اصلاح، اصلاحیه جدید ثبت کنید.');
            }
        });

        static::deleting(function (): void {
            throw new WorkflowException('حذف پیام‌های پرونده مجاز نیست.');
        });
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RequestAssignment::class, 'assignment_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function corrects(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_message_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_type' => MessageType::class,
            'visibility' => MessageVisibility::class,
            'is_final' => 'boolean',
        ];
    }
}
