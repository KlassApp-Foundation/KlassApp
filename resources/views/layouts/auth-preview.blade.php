{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview-only auth layout. Live auth pages keep layouts.empty until cutover. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('layouts.partials.favicon')
  <title>@yield('title', config('app.name', 'KlassApp')) · Preview</title>
  @vite(['resources/css/auth-preview.css'])
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  @stack('styles')
</head>
<body class="ap-body">
  @yield('content')
  @stack('scripts')
</body>
</html>
