<?php

namespace App\Models;

use Database\Factories\CustomerAccessSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id',
    'token_hash',
    'expires_at',
    'revoked_at',
    'last_seen_at',
    'ip_address',
    'user_agent',
])]
class CustomerAccessSession extends Model
{
    /** @use HasFactory<CustomerAccessSessionFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
