@props([
    'variant' => 'info',
    'size' => 'sm',
    'class' => '',
])

@php
    $knownVariants = ['pending', 'approved', 'rejected', 'paid', 'unpaid', 'active', 'inactive', 'warning', 'info'];
    $safeVariant = in_array($variant, $knownVariants) ? $variant : 'info';
    $variantClass = 'ds-badge-' . $safeVariant;
    $sizeClass = 'ds-badge-' . $size;
    $classes = 'ds-badge ' . $variantClass . ' ' . $sizeClass . ' ' . $class;
@endphp

<span class="{{ $classes }}">{{ $slot }}</span>
