<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Database\Factories\RequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_request_id', 'from_status', 'to_status', 'changed_by', 'reason', 'metadata', 'created_at'])]
class RequestStatusHistory extends Model
{
    /** @use HasFactory<RequestStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
