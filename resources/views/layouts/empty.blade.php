{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.favicon')
    <title>{{ config('app.name', 'KlassApp') }}</title>
    {{-- Auth / OTP pages must not boot the global Vue app.
         Mounting app.js on #app can wipe inputs typed before the bundle finishes loading,
         because Vue recompiles the server-rendered markup from empty value attributes. --}}
    @vite([
        'resources/assets/sass/app.scss',
        'resources/css/tailwind.css',
    ])
    <link href="{{ asset('css/dashboard-refresh.css') }}?v={{ filemtime(public_path('css/dashboard-refresh.css')) }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @stack('styles')
  </head>
  <body style="background: #FAFAF5; font-family: 'DM Sans', sans-serif;">
    <main>
      <div class="flex items-center justify-center min-h-screen px-4 py-8">
        @yield('content')
      </div>
    </main>
    @stack('scripts')
  </body>
</html>
