<?php

namespace App\Models;

use App\Enums\FieldType;
use Database\Factories\RequestFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'request_type_id',
    'label',
    'field_key',
    'field_type',
    'validation_rules',
    'options',
    'is_required',
    'is_active',
    'sort_order',
])]
class RequestField extends Model
{
    /** @use HasFactory<RequestFieldFactory> */
    use HasFactory;

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(RequestFieldValue::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_type' => FieldType::class,
            'validation_rules' => 'array',
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
