{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview-only Pass-2 error shell (auth-error.html frames 404/419/500).
     Path: resources/views/errors-preview/ — NOT resources/views/errors/ (live cutover is a separate decision).
     Self-contained CSS — do NOT @vite here (mirrors live error reliability).
     Typography: Sora/DM Sans (live auth + errors.illustrated-layout), not landing Bricolage/Inter.
     Vintage paper bg: OD klassapp-auth-error-vintage-paper-v1.html (additive; fonts unchanged).
     Preview routes pass a synthetic $exception; never echo raw exception text on 500. --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') · {{ config('app.name', 'KlassApp') }}</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --paper-base: #F5F0E6;
            --paper-mid: #EFE6D5;
            --paper-deep: #E8DCC8;
            --paper-edge: #D4C4A8;
            --paper-ink-wash: rgba(92, 74, 48, 0.03);
            --paper-rule: rgba(120, 95, 60, 0.07);
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper-base);
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse 90% 80% at 50% 30%, var(--paper-base) 0%, var(--paper-mid) 55%, var(--paper-deep) 100%),
                radial-gradient(ellipse 75% 70% at 50% 40%, transparent 40%, var(--paper-ink-wash) 100%),
                linear-gradient(180deg, rgba(212, 196, 168, 0.16) 0%, transparent 18%, transparent 82%, rgba(196, 176, 140, 0.18) 100%),
                linear-gradient(90deg, rgba(180, 155, 110, 0.09) 0%, transparent 8%, transparent 92%, rgba(180, 155, 110, 0.08) 100%),
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 31px,
                    var(--paper-rule) 31px,
                    var(--paper-rule) 32px
                );
        }
        body::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.22;
            mix-blend-mode: multiply;
            background-image:
                url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.28'/%3E%3C/svg%3E");
            background-size: 180px 180px;
        }
        .err-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            background: #FFFFFF;
            border: 1px solid color-mix(in srgb, var(--paper-edge) 45%, #E2E8F0);
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1), 0 4px 8px rgba(15, 23, 42, 0.04);
        }
        .err-logo {
            display: block;
            height: 36px;
            width: auto;
            margin: 0 auto 28px;
        }
        .err-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            margin: 0 auto 24px;
        }
        .err-icon svg { width: 28px; height: 28px; }
        .err-icon-blue  { background: rgba(30, 111, 217, 0.08); color: #1E6FD9; }
        .err-icon-amber { background: rgba(217, 119, 6, 0.08); color: #D97706; }
        .err-icon-dark  { background: rgba(15, 23, 42, 0.06); color: #475569; }
        .err-code {
            font-family: ui-monospace, 'JetBrains Mono', Menlo, monospace;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94A3B8;
            margin-bottom: 8px;
        }
        .err-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(22px, 4vw, 26px);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0F172A;
            margin-bottom: 12px;
            text-wrap: balance;
        }
        .err-msg {
            font-size: 14px;
            color: #64748B;
            line-height: 1.65;
            max-width: 38ch;
            margin: 0 auto 28px;
            text-wrap: pretty;
        }
        .err-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .err-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 24px;
            min-height: 44px;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: -0.01em;
            text-decoration: none;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .err-btn-primary {
            background: #22C55E;
            color: #fff;
            border-color: #22C55E;
            box-shadow: 0 1px 3px rgba(34, 197, 94, 0.28);
        }
        .err-btn-primary:hover { background: #16A34A; border-color: #16A34A; }
        .err-btn-secondary {
            background: transparent;
            color: #64748B;
            border-color: #E2E8F0;
        }
        .err-btn-secondary:hover { background: #F8FAFC; border-color: #CBD5E1; }
        .err-btn-amber {
            background: #D97706;
            color: #fff;
            border-color: #D97706;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.28);
        }
        .err-btn-amber:hover { background: #B45309; border-color: #B45309; }
        .err-preview-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1E6FD9;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 999px;
            padding: 4px 10px;
        }
        @media (max-width: 480px) {
            body { padding: 16px; }
            .err-card { padding: 36px 24px; }
        }
    </style>
    @stack('styles')
</head>
<body data-error-shell="pass2" data-error-paper="vintage" data-error-code="@yield('code')">
    <div class="err-card">
        @if (! empty($isPreview))
            <span class="err-preview-badge">Preview</span>
        @endif
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="err-logo" alt="KlassApp">
        @yield('icon')
        <div class="err-code">Error @yield('code')</div>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-msg">@yield('message')</p>
        <div class="err-actions">
            @yield('actions')
        </div>
        {{-- $exception is available from Laravel; do not dump it here. --}}
        @isset($exception)
            <span data-testid="exception-present" data-exception-class="{{ get_class($exception) }}" hidden></span>
        @endisset
    </div>
</body>
</html>
