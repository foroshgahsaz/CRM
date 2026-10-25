@foreach ($types as $type)
    <div
        @class([
            'grid gap-3' => ($variant ?? 'crm') === 'crm',
            'cb-type-fields' => ($variant ?? 'crm') === 'premium',
        ])
        data-type-fields="{{ $type->id }}"
        hidden
    >
        @foreach ($type->activeFields as $field)
            @include('public.partials.request-type-field-input', [
                'field' => $field,
                'variant' => $variant ?? 'crm',
                'value' => old('fields.'.$field->field_key, ($prefill['fields'] ?? [])[$field->field_key] ?? null),
            ])
        @endforeach
    </div>
@endforeach
