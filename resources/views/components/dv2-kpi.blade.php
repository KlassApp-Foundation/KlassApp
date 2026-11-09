{{-- SPDX-License-Identifier: MIT --}}
{{--
    Dashboard stat tile: the school admin dashboard's card (admin/dashboard/dashboard_v2.blade.php),
    as a component so another role's dashboard looks and behaves the same. Markup and classes
    are the admin's (.dv2-kpi*, public/css/dashboard-v2.css): a muted 16px Lucide icon beside the
    label, the value in Sora, an optional sub line, the whole tile one link.

    <x-dv2-kpi icon="graduation-cap" label="My Students" :value="36" sub="in your classes" :href="url('/teacher/classes')" />

    Props
      label    tile label (also the accessible name, with the value)
      value    the number or text
      icon     Lucide name for <x-ka-icon> at 16px; 'whatsapp' draws the WhatsApp mark instead
      sub      optional line under the value
      subTone  '' | 'up' | 'down' | 'warn'   (colours the sub line)
      href     makes the whole tile a link; without it the tile is plain, not clickable
      waiting  true => the amber "needs you" treatment the admin WhatsApp tile uses
    Extra attributes (data-testid, ...) go on the tile.
--}}
@props([
    'label' => '',
    'value' => '–',
    'icon' => null,
    'sub' => null,
    'subTone' => '',
    'href' => null,
    'waiting' => false,
])
@once
    <link rel="stylesheet" href="{{ asset('css/dashboard-v2.css') }}?v={{ filemtime(public_path('css/dashboard-v2.css')) }}">
@endonce
@php
    $aria = trim($label.', '.$value.($sub ? ' '.$sub : ''));
    $tag = $href ? 'a' : 'div';
@endphp
<div {{ $attributes->class(['dv2-kpi', 'dv2-kpi--waiting' => $waiting, 'dv2-kpi--static' => ! $href])->merge($href ? [] : ['style' => 'cursor:default']) }}>
    <{{ $tag }} class="dv2-kpi-link" @if($href) href="{{ $href }}" aria-label="{{ $aria }}, open" @endif>
        <span class="dv2-kpi-label">
            @if($icon === 'whatsapp')
                <x-brand.whatsapp width="18" height="18" />
            @elseif($icon)
                <x-ka-icon :name="$icon" :size="16" tone="muted" />
            @endif
            {{ $label }}
        </span>
        <span class="dv2-kpi-value">{{ $value }}</span>
        @if($sub)
            <p class="dv2-kpi-sub {{ $subTone }}">{{ $sub }}</p>
        @endif
    </{{ $tag }}>
</div>
