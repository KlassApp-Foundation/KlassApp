@props([
    'padding' => 'default',
    'shadow' => 'sm',
    'hover' => false,
    'class' => '',
    'title' => null,
])

@php
    $paddingClass = match($padding) {
        'default' => 'ds-card-padding-default',
        'sm' => 'ds-card-padding-sm',
        'none' => 'ds-card-padding-none',
        'lg' => 'ds-card-padding-lg',
        default => 'ds-card-padding-default',
    };
    $shadowClass = match($shadow) {
        'sm' => 'ds-card-shadow-sm',
        'md' => 'ds-card-shadow-md',
        'lg' => 'ds-card-shadow-lg',
        'none' => '',
        default => 'ds-card-shadow-sm',
    };
    $hoverClass = $hover ? 'ds-card-hover' : '';
    $classes = 'ds-card ' . $paddingClass . ' ' . $shadowClass . ' ' . $hoverClass . ' ' . $class;
@endphp

<div class="{{ $classes }}">
    @if($title)
        <h3 class="ds-card-title">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
