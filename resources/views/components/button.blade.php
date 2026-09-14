@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'class' => '',
    'disabled' => false,
])

@php
    $variantClass = match($variant) {
        'primary' => 'ds-btn-primary',
        'success' => 'ds-btn-success',
        'danger' => 'ds-btn-danger',
        'warning' => 'ds-btn-warning',
        'outline' => 'ds-btn-outline',
        'ghost' => 'ds-btn-ghost',
        default => 'ds-btn-primary',
    };
    $sizeClass = match($size) {
        'sm' => 'ds-btn-sm',
        'md' => 'ds-btn-md',
        'lg' => 'ds-btn-lg',
        default => 'ds-btn-md',
    };
    $classes = trim('ds-btn '.$variantClass.' '.$sizeClass.' '.$class);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} @if($disabled) aria-disabled="true" tabindex="-1" @endif>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => $type])->class($classes) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif
