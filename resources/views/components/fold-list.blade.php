{{-- SPDX-License-Identifier: MIT --}}
{{--
  Shows the first few entries of a long list, then "Show N more" / "Show less".
  Everything is still rendered. Opening and closing happens in the browser.

  Items: <x-fold-list tag="ul" class="..."> @foreach ... @endforeach </x-fold-list>
  Tables: <x-fold-list table> ...existing table... </x-fold-list>
--}}
@props([
    'limit' => 5,
    'tag' => 'div',
    'table' => false,
    'name' => null,
])

@php
    $limit = max(1, (int) $limit);
    $asTable = $table === true || $table === 1 || $table === '1' || $table === 'true';
    $tag = strtolower((string) $tag);
    if (! in_array($tag, ['div', 'ul', 'ol', 'section'], true)) {
        $tag = 'div';
    }

    $slotHtml = $slot->toHtml();
    $count = $asTable
        ? \App\Support\FoldListHtml::tbodyRowCount($slotHtml)
        : \App\Support\FoldListHtml::elementCount($slotHtml);
    $more = max(0, $count - $limit);
    // Stable across a Livewire refresh (the link-request list polls) so "Show less" stays open.
    $stateName = trim((string) ($name ?? ''));
    if ($stateName === '') {
        $stateName = trim((string) ($attributes->get('data-testid') ?? ''));
    }
    $stateName = strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $stateName));
    $stateName = trim($stateName, '-');
    if ($stateName === '') {
        $stateName = bin2hex(random_bytes(4));
    }
    $rootId = 'fold-'.$stateName;
    $from = $limit + 1;
@endphp

@if($more === 0)
    @if($asTable)
        {!! $slotHtml !!}
    @else
        <{{ $tag }} {{ $attributes }}>
            {!! $slotHtml !!}
        </{{ $tag }}>
    @endif
@else
    @once
        <style>
            [x-cloak] { display: none !important; }
            .fold-list__button {
                display: inline-flex;
                align-items: center;
                min-height: 44px;
                margin: 8px 0 0;
                padding: 8px 2px;
                border: 0;
                background: transparent;
                color: #0F172A;
                font: inherit;
                font-weight: 600;
                font-size: 14px;
                line-height: 1.3;
                text-align: left;
                cursor: pointer;
            }
            .fold-list__button:hover { color: #15803D; }
            .fold-list__button:focus-visible {
                outline: 2px solid #15803D;
                outline-offset: 2px;
            }
            .fold-list { margin: 0 0 14px; }
            .fold-list > .fold-list__items { margin-bottom: 0; }
        </style>
    @endonce
    <style>
        #{{ $rootId }}:not(.is-open) > .fold-list__items > :nth-child(n+{{ $from }}) { display: none; }
        #{{ $rootId }}:not(.is-open).fold-list--table tbody > tr:nth-child(n+{{ $from }}) { display: none; }
    </style>
    <div id="{{ $rootId }}" @class(['fold-list', 'fold-list--table' => $asTable]) x-data="{ open: !!(window.foldOpen && window.foldOpen['{{ $rootId }}']) }" x-bind:class="{ 'is-open': open }">
        @if($asTable)
            {!! $slotHtml !!}
        @else
            <{{ $tag }} {{ $attributes->merge(['class' => 'fold-list__items']) }}>
                {!! $slotHtml !!}
            </{{ $tag }}>
        @endif
        <button
            type="button"
            class="fold-list__button"
            aria-expanded="false"
            aria-controls="{{ $rootId }}"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="open = ! open; window.foldOpen = window.foldOpen || {}; window.foldOpen['{{ $rootId }}'] = open"
        >
            <span x-show="! open">Show {{ $more }} more</span>
            <span x-show="open" x-cloak>Show less</span>
        </button>
    </div>
@endif
