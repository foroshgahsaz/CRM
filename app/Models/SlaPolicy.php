<?php

namespace App\Models;

use App\Enums\RequestPriority;
use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'request_type_id',
    'priority',
    'organizational_unit_id',
    'response_minutes',
    'resolution_minutes',
    'use_business_hours',
    'is_active',
])]
class SlaPolicy extends Model
{
    /** @use HasFactory<SlaPolicyFactory> */
    use HasFactory;

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => RequestPriority::class,
            'use_business_hours' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
