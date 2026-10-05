{{-- SPDX-License-Identifier: MIT --}}
{{--
    Sidebar icon lookup. Maps a config/navigation.php `icon` key to a Lucide name through
    config('navigation.icons') and draws it with <x-ka-icon> (20px, stroke 2). Colour is
    CSS-only, via the --d-icon tokens (.ka-nav-icon in public/css/dashboard-refresh.css):
    no fill or duotone layer, the active state comes from the row background and weight.
--}}
@props(['name' => null])
<x-ka-icon :name="config('navigation.icons.'.$name, 'info')" :size="20" tone="muted" class="ka-nav-icon" />
