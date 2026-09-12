{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview-only Pass-2 error shell (auth-error.html frames 404/419/500).
     Path: resources/views/errors-preview/ - NOT resources/views/errors/ (live cutover is a separate decision).
     Self-contained CSS - do NOT @vite here (mirrors live error reliability).
     Typography: Sora/DM Sans (live auth + errors.illustrated-layout), not landing Bricolage/Inter.
     Vintage paper: exact landing .hero-bg-vintage layers. OD v2 breakpoints: desktop split / mobile stack.
     Transparent shell (no opaque white card). Preview routes pass synthetic $exception; never echo raw 500 text. --}}
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
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }
        /* Exact landing .hero-bg-vintage */
        .err-bg-vintage {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background:
                radial-gradient(ellipse 90% 80% at 50% 30%, var(--paper-base) 0%, var(--paper-mid) 55%, var(--paper-deep) 100%);
        }
        .err-bg-vintage::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
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
        .err-bg-vintage::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.22;
            mix-blend-mode: multiply;
            background-image:
                url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.28'/%3E%3C/svg%3E");
            background-size: 180px 180px;
        }
        .err-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }
        .err-brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 28px 20px 8px;
        }
        .err-brand-logo {
            display: block;
            height: 36px;
            width: auto;
        }
        .err-brand-tagline {
            margin: 8px 0 0;
            font-family: 'Sora', sans-serif;
            font-size: 1.375rem;
            font-weight: 600;
            letter-spacing: -0.03em;
            line-height: 1.25;
            color: #0F172A;
            max-width: 18ch;
        }
        .err-brand-support {
            margin: 0;
            font-size: 0.875rem;
            color: #64748B;
            line-height: 1.5;
            max-width: 32ch;
        }
        .err-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 8px 20px 32px;
        }
        /* Transparent shell - paper shows through */
        .err-card {
            position: relative;
            width: 100%;
            max-width: 480px;
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            text-align: left;
            box-shadow: none;
        }
        .err-logo { display: none; }
        .err-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            margin: 0 0 24px;
            background: color-mix(in srgb, #fff 50%, transparent);
            backdrop-filter: blur(6px);
        }
        .err-icon svg { width: 28px; height: 28px; }
        .err-icon-blue  { color: #1E6FD9; }
        .err-icon-amber { color: #D97706; }
        .err-icon-dark  { color: #475569; }
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
            margin: 0 0 28px;
            text-wrap: pretty;
        }
        .err-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 320px;
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
            background: color-mix(in srgb, #fff 50%, transparent);
            color: #64748B;
            border-color: color-mix(in srgb, var(--paper-edge) 60%, #E2E8F0);
            backdrop-filter: blur(6px);
        }
        .err-btn-secondary:hover { background: color-mix(in srgb, #fff 70%, transparent); border-color: #CBD5E1; }
        .err-btn-amber {
            background: #D97706;
            color: #fff;
            border-color: #D97706;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.28);
        }
        .err-btn-amber:hover { background: #B45309; border-color: #B45309; }
        .err-preview-badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1E6FD9;
            background: color-mix(in srgb, #EFF6FF 80%, transparent);
            border: 1px solid #BFDBFE;
            border-radius: 999px;
            padding: 4px 10px;
        }
        @media (min-width: 960px) {
            .err-shell {
                display: grid;
                grid-template-columns: 45% 55%;
                align-items: stretch;
            }
            .err-brand-panel {
                justify-content: center;
                padding: 64px 72px 64px 80px;
                border-right: 1px solid color-mix(in srgb, var(--paper-edge) 40%, transparent);
            }
            .err-brand-panel .err-preview-badge {
                position: absolute;
                top: 32px;
                left: 80px;
            }
            .err-brand-logo { height: 40px; margin-bottom: 20px; }
            .err-brand-tagline { font-size: 1.75rem; margin-top: 0; }
            .err-brand-support { margin-top: 16px; font-size: 0.9375rem; }
            .err-form-panel {
                justify-content: center;
                padding: 64px 96px 64px 72px;
            }
        }
    </style>
    @stack('styles')
</head>
<body data-error-shell="pass2" data-error-paper="vintage" data-error-layout="split" data-error-code="@yield('code')">
    <div class="err-bg-vintage" aria-hidden="true"></div>
    <div class="err-shell">
        <aside class="err-brand-panel">
            @if (! empty($isPreview))
                <span class="err-preview-badge">Preview</span>
            @endif
            <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="err-brand-logo" alt="KlassApp">
            <p class="err-brand-tagline">Something went off the page</p>
            <p class="err-brand-support">You are still on KlassApp. Use the actions to get back on track.</p>
        </aside>
        <div class="err-form-panel">
            <div class="err-card">
                <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="err-logo" alt="KlassApp">
                @yield('icon')
                <div class="err-code">Error @yield('code')</div>
                <h1 class="err-title">@yield('title')</h1>
                <p class="err-msg">@yield('message')</p>
                <div class="err-actions">
                    @yield('actions')
                </div>
                @isset($exception)
                    <span data-testid="exception-present" data-exception-class="{{ get_class($exception) }}" hidden></span>
                @endisset
            </div>
        </div>
    </div>
</body>
</html>
