@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => '',
    'required' => false,
    'maxToday' => false,
    'placeholder' => 'انتخاب از تقویم',
])

@php($inputId = $id ?? $name)

@if ($label)
    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="{{ $inputId }}">
        {{ $label }}
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="text"
            value="{{ $value }}"
            data-jdp=""
            autocomplete="off"
            @if ($maxToday) data-jdp-max-date="today" @endif
            @required($required)
            {{ $attributes->class(['crm-input font-normal', 'crm-jalali-input']) }}
            placeholder="{{ $placeholder }}"
        >
    </label>
@else
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="text"
        value="{{ $value }}"
        data-jdp
        autocomplete="off"
        @if ($maxToday) data-jdp-max-date="today" @endif
        @required($required)
        {{ $attributes->class(['crm-input font-normal', 'crm-jalali-input']) }}
        placeholder="{{ $placeholder }}"
    >
@endif
