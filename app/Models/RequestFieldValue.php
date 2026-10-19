<?php

namespace App\Models;

use App\Enums\FieldType;
use Database\Factories\RequestFieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id',
    'request_field_id',
    'field_key',
    'label_snapshot',
    'field_type',
    'value_text',
    'value_number',
    'value_date',
    'value_json',
])]
class RequestFieldValue extends Model
{
    /** @use HasFactory<RequestFieldValueFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(RequestField::class, 'request_field_id');
    }

    public function displayValue(): string
    {
        return match ($this->field_type) {
            FieldType::Number => $this->value_number === null ? '—' : (string) $this->value_number,
            FieldType::Date => $this->value_date?->format('Y-m-d') ?? '—',
            FieldType::Boolean => $this->value_text === '1' ? 'بله' : 'خیر',
            default => (string) ($this->value_text ?? '—'),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field_type' => FieldType::class,
            'value_number' => 'decimal:2',
            'value_date' => 'date',
            'value_json' => 'array',
        ];
    }
}
