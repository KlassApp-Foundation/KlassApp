{{-- SPDX-License-Identifier: MIT --}}
{{--
    THE icon wrapper (design/system handoff-2026-10-01-icons). Views never call the
    Lucide package components (<x-lucide-*>) directly, so a future icon-set change
    touches this one file.

    Named <x-ka-icon>, not <x-icon>: blade-ui-kit/blade-icons registers its own
    default <x-icon> component, and that registration wins over a file in
    components/. KaIcon.vue uses the same name on the Vue side.

    Props
      name   Lucide icon name (kebab-case). Unknown names render `info`
             (and log a warning outside production).
      size   16 | 20 (default) | 24. Sets width/height.
      tone   default | muted | active | danger | warning | on-accent. Picks one of the
             six --d-icon* tokens (see .ka-icon--* in public/css/dashboard-refresh.css).
      stroke Defaults to 2 at 16/20 and 1.75 at 24 and up.

    Icons are decorative by default (aria-hidden): the label sits next to them. An
    icon-only button carries aria-label on the BUTTON, not on the svg.
--}}
@props(['name' => 'info', 'size' => 20, 'tone' => 'default', 'stroke' => null])
@php
    $size = (int) $size > 0 ? (int) $size : 20;
    $stroke = $stroke ?? ($size >= 24 ? 1.75 : 2);
    $name = (string) $name;
    $tone = in_array($tone, ['default', 'muted', 'active', 'danger', 'warning', 'on-accent'], true) ? $tone : 'default';

    $resolved = false;
    if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name) === 1) {
        try {
            app(\BladeUI\Icons\Factory::class)->svg('lucide-'.$name);
            $resolved = true;
        } catch (\BladeUI\Icons\Exceptions\SvgNotFound $e) {
            $resolved = false;
        }
    }
    if (! $resolved) {
        if (! app()->isProduction()) {
            logger()->warning('<x-ka-icon>: unknown Lucide icon ['.$name.'], rendering [info].');
        }
        $name = 'info';
    }
@endphp
<x-dynamic-component
    :component="'lucide-'.$name"
    {{ $attributes->merge([
        'class' => 'ka-icon ka-icon--'.$tone,
        'width' => $size,
        'height' => $size,
        'stroke-width' => $stroke,
        'aria-hidden' => 'true',
        'focusable' => 'false',
    ]) }}
/>
