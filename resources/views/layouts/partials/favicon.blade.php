{{-- SPDX-License-Identifier: MIT --}}
{{-- KlassApp brand icons — generated from public/images/klassapp-logo.svg (not GeGo leftovers). --}}
@php
    $svgPath = 'images/klassapp-logo.svg';
    $icon16 = 'favicon/favicon-16x16.png';
    $icon32 = 'favicon/favicon-32x32.png';
    $appleTouch = 'favicon/apple-icon-180x180.png';
    $version = @filemtime(public_path($svgPath)) ?: time();
@endphp
<link rel="icon" type="image/svg+xml" href="{{ asset($svgPath) }}?v={{ $version }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset($icon32) }}?v={{ $version }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset($icon16) }}?v={{ $version }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset($appleTouch) }}?v={{ $version }}">
<link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
<meta name="msapplication-TileColor" content="#199D52">
<meta name="msapplication-config" content="{{ asset('favicon/browserconfig.xml') }}">
<meta name="theme-color" content="#199D52">
