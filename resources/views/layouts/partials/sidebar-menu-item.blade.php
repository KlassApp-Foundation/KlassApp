{{-- SPDX-License-Identifier: MIT --}}
{{-- One sidebar item, rendered from config('navigation.*'). --}}
@php
    /** @var array $item */
    $href = $navHref($item);
    $active = $navActive($item);
    // An item-level `class` replaces the role default entirely (as the retired
    // per-role menus did for their child items).
    $liClasses = trim((($item['class'] ?? '') ?: $itemClass).' '.($active ? $activeClass : ''));
    $aClass = $item['a_class'] ?? 'flex items-center';
    $spanClass = ($item['small'] ?? false) ? '' : 'mx-3 whitespace-nowrap';
@endphp
<li class="{{ $liClasses }}">
    <a href="{{ $href }}" class="{{ $aClass }}"
       @isset($item['title']) title="{{ $item['title'] }}" @endisset
       @isset($item['testid']) data-testid="{{ $item['testid'] }}" @endisset>
        @if(!empty($item['icon']))
            <x-icons.sidebar name="{{ $item['icon'] }}"/>
        @endif
        <span @if($spanClass) class="{{ $spanClass }}" @endif>{{ $item['label'] }}</span>
    </a>
</li>
