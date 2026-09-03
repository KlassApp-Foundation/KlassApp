{{-- SPDX-License-Identifier: MIT --}}
{{-- Clean, calm error layout inspired by Flareapp.io --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') — {{ config('app.name', 'KlassApp') }}</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #FAFAF5;
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .klass-error-shell {
            width: 100%;
            max-width: 480px;
            text-align: center;
        }
        .klass-error-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .klass-error-code {
            font-family: 'Sora', sans-serif;
            font-size: clamp(64px, 15vw, 96px);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
            color: #E2E8F0;
            margin-bottom: 8px;
        }
        .klass-error-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(20px, 4vw, 28px);
            font-weight: 700;
            color: #0F172A;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .klass-error-message {
            font-size: 15px;
            line-height: 1.7;
            color: #64748B;
            margin-bottom: 32px;
        }
        .klass-error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .klass-error-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
        }
        .klass-error-btn-primary {
            background: #22C55E;
            color: #fff;
        }
        .klass-error-btn-primary:hover {
            background: #16A34A;
        }
        .klass-error-btn-secondary {
            background: #fff;
            color: #1E293B;
            border: 1px solid #E2E8F0;
        }
        .klass-error-btn-secondary:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }
        @media (max-width: 480px) {
            body { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="klass-error-shell">
        <div style="margin-bottom:32px;">
            <img src="{{ asset('images/klassapp-logo.svg') }}" alt="KlassApp" style="height:36px;width:auto;">
        </div>
        @yield('icon', '')
        @hasSection('code')
        <div class="klass-error-code">@yield('code')</div>
        @endif
        <h1 class="klass-error-title">@yield('title')</h1>
        <p class="klass-error-message">@yield('message')</p>
        <div class="klass-error-actions">
            @hasSection('actions')
                @yield('actions')
            @else
            <a href="{{ url('/') }}" class="klass-error-btn klass-error-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Go Home
            </a>
            <button onclick="window.history.back()" class="klass-error-btn klass-error-btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Go Back
            </button>
            @endif
        </div>
    </div>
</body>
</html>
