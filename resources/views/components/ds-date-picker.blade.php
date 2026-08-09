{{-- SPDX-License-Identifier: MIT --}}
{{--
  Accessible date picker: native <input type="date"> (already used across KlassApp)
  wrapped in Wave DS chrome. No new JS dependency — Alpine ships with Livewire 3.
--}}
@props([
    'id',
    'label',
    'required' => false,
    'hint' => null,
])

@php
    $inputClasses = 'ds-form-input ds-date-picker-input w-full';
@endphp

<div class="ds-form-group ds-date-picker" data-testid="{{ $attributes->get('data-testid', $id) }}-wrap">
    <label class="ds-form-label" for="{{ $id }}">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div
        class="ds-date-picker-field"
        x-data="{
            openPicker() {
                const el = this.$refs.input;
                if (!el) return;
                if (typeof el.showPicker === 'function') {
                    try { el.showPicker(); return; } catch (e) {}
                }
                el.focus();
                el.click();
            }
        }"
    >
        <button
            type="button"
            class="ds-date-picker-trigger"
            @click.prevent="openPicker()"
            tabindex="-1"
            aria-hidden="true"
            title="Open calendar"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </button>

        <input
            type="date"
            id="{{ $id }}"
            x-ref="input"
            {{ $attributes->except('data-testid')->merge(['class' => $inputClasses]) }}
            @if($required) required @endif
            autocomplete="off"
        />
    </div>

    @if($hint)
        <p class="ds-date-picker-hint">{{ $hint }}</p>
    @endif
</div>
