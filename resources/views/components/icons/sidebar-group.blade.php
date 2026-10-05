{{-- SPDX-License-Identifier: MIT --}}
{{-- Group-header glyphs for the data-driven sidebar (layouts/partials/sidebar-menu).
     Lucide at 16px (dense), muted tone, via config('navigation.group_icons').
     Dashboard v2 makes group labels text-only; remove this component then. --}}
@props(['name' => null])
<x-ka-icon :name="config('navigation.group_icons.'.$name, 'info')" :size="16" tone="muted" class="ka-group-icon" />
