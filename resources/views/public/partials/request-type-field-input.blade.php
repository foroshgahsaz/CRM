@php
    $name = 'fields['.$field->field_key.']';
    $required = $field->is_required;
    $value = $value ?? old('fields.'.$field->field_key);
@endphp

@if ($variant === 'premium')
    <label @class(['field', 'full' => in_array($field->field_type->value, ['textarea'], true)])>{{ $field->label }}
        @if ($field->field_type->value === 'textarea')
            <textarea name="{{ $name }}" @required($required)>{{ $value }}</textarea>
        @elseif ($field->field_type->value === 'select')
            <select name="{{ $name }}" @required($required)>
                <option value="">انتخاب کنید</option>
                @foreach ($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                @endforeach
            </select>
        @elseif ($field->field_type->value === 'date')
            <input
                name="{{ $name }}"
                class="cb-jalali-input"
                data-jdp=""
                autocomplete="off"
                placeholder="انتخاب از تقویم"
                value="{{ $value }}"
                @required($required)
            >
        @else
            <input name="{{ $name }}" value="{{ $value }}" @required($required)>
        @endif
    </label>
@else
    <label class="grid gap-1.5 text-sm font-semibold text-brand-900">{{ $field->label }}
        @if ($field->field_type->value === 'textarea')
            <textarea name="{{ $name }}" class="crm-input font-normal" @required($required)>{{ $value }}</textarea>
        @elseif ($field->field_type->value === 'select')
            <select name="{{ $name }}" class="crm-input font-normal" @required($required)>
                <option value="">انتخاب کنید</option>
                @foreach ($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                @endforeach
            </select>
        @elseif ($field->field_type->value === 'date')
            <input
                name="{{ $name }}"
                class="crm-input crm-jalali-input font-normal"
                data-jdp=""
                autocomplete="off"
                placeholder="انتخاب از تقویم"
                value="{{ $value }}"
                @required($required)
            >
        @else
            <input name="{{ $name }}" value="{{ $value }}" class="crm-input font-normal" @required($required)>
        @endif
    </label>
@endif
