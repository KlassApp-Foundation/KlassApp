@props([
    'label' => null,
    'name' => '',
    'type' => 'text',
    'value' => null,
    'error' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'options' => [],
    'class' => '',
])

@php
    $labelClass = 'ds-form-label' . ($required ? ' ds-form-label-required' : '');
    $inputClass = 'ds-form-input' . ($error ? ' ds-form-input-error' : '');
    if ($type === 'select') {
        $inputClass .= ' ds-form-select';
    }
    if ($type === 'textarea') {
        $inputClass .= ' ds-form-textarea';
    }
    $inputId = $name ?: Str::slug($label ?? '');
@endphp

<div class="ds-form-group {{ $class }}">
    @if($label)
        <label for="{{ $inputId }}" class="{{ $labelClass }}">{{ $label }}</label>
    @endif

    @if($type === 'select')
        <select name="{{ $name }}" id="{{ $inputId }}" class="{{ $inputClass }}"
            {{ $required ? 'required' : '' }}>
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $key => $option)
                <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $inputId }}" class="{{ $inputClass }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}>{{ old($name, $value) }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}" value="{{ old($name, $value) }}"
            class="{{ $inputClass }}" placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}>
    @endif

    @if($error)
        <p class="ds-form-error">{{ $error }}</p>
    @endif
    @if($help)
        <p class="ds-form-help">{{ $help }}</p>
    @endif

    {{ $slot ?? '' }}
</div>
