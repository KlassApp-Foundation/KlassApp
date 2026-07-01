{{-- SPDX-License-Identifier: MIT --}}
@php
    $svgPath = 'images/klassapp-logo.svg';
    $pngPath = file_exists(public_path('favicon/favicon-32x32.png')) ? 'favicon/favicon-32x32.png' : null;
    $version = @filemtime(public_path($svgPath)) ?: time();
@endphp
<link rel="icon" type="image/svg+xml" href="{{ asset($svgPath) }}?v={{ $version }}">
@if($pngPath)
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset($pngPath) }}?v={{ $version }}">
@endif
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-icon-180x180.png') }}?v={{ $version }}">
<link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
<meta name="theme-color" content="#ffffff">
