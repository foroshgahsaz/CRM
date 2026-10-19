<?php

namespace App\Services;

use App\Enums\FieldType;
use App\Models\RequestField;
use App\Models\RequestFieldValue;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DynamicFieldService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(RequestType $type, array $input): array
    {
        $rules = [];
        $attributes = [];

        foreach ($type->activeFields()->get() as $field) {
            $key = 'fields.'.$field->field_key;
            $rules[$key] = $this->rulesFor($field);
            $attributes[$key] = $field->label;
        }

        $validated = Validator::make($input, $rules, [
            'required' => 'تکمیل :attribute الزامی است.',
        ], $attributes)->validate();

        return $validated['fields'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function store(ServiceRequest $request, array $fields): void
    {
        $definitions = $request->requestType->fields()->where('is_active', true)->get();

        foreach ($definitions as $definition) {
            if (! array_key_exists($definition->field_key, $fields)) {
                continue;
            }

            $value = $fields[$definition->field_key];

            if ($value === null || $value === '') {
                continue;
            }

            RequestFieldValue::query()->create([
                'service_request_id' => $request->id,
                'request_field_id' => $definition->id,
                'field_key' => $definition->field_key,
                'label_snapshot' => $definition->label,
                'field_type' => $definition->field_type,
                'value_text' => $this->textValue($definition->field_type, $value),
                'value_number' => $definition->field_type === FieldType::Number ? $value : null,
                'value_date' => $definition->field_type === FieldType::Date ? $value : null,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function rulesFor(RequestField $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];

        $rules[] = match ($field->field_type) {
            FieldType::Number => 'numeric',
            FieldType::Date => 'date',
            FieldType::Boolean => 'boolean',
            default => 'string',
        };

        if ($field->field_type === FieldType::Text) {
            $rules[] = 'max:255';
        }

        if ($field->field_type === FieldType::Textarea) {
            $rules[] = 'max:2000';
        }

        if ($field->field_type === FieldType::Select) {
            $rules[] = Rule::in($field->options ?? []);
        }

        foreach ($field->validation_rules ?? [] as $rule) {
            if (is_string($rule) && $this->isAllowedStoredRule($rule)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    public function isAllowedStoredRule(string $rule): bool
    {
        if (in_array($rule, ['string', 'integer', 'numeric', 'boolean', 'date', 'email'], true)) {
            return true;
        }

        return (bool) preg_match('/^(min|max):([1-9]\d{0,3})$/', $rule);
    }

    private function textValue(FieldType $type, mixed $value): ?string
    {
        if ($type === FieldType::Number || $type === FieldType::Date) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
