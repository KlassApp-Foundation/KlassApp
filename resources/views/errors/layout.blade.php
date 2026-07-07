{{-- SPDX-License-Identifier: MIT --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
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
        .klass-error-code {
            font-family: 'Sora', sans-serif;
            font-size: clamp(48px, 12vw, 80px);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #E2E8F0;
            line-height: 1;
            margin-bottom: 12px;
        }
        .klass-error-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(18px, 3.5vw, 24px);
            font-weight: 700;
            color: #0F172A;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        .klass-error-msg {
            font-size: 15px;
            line-height: 1.7;
            color: #64748B;
            margin-bottom: 24px;
        }
        .klass-error-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 24px;
            border-radius: 10px;
            background: #22C55E;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.15s ease;
        }
        .klass-error-link:hover { background: #16A34A; }
    </style>
</head>
<body>
    <div class="klass-error-shell">
        <div style="margin-bottom:32px;">
            <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" style="height:36px;width:auto;">
        </div>
        <div class="klass-error-code">@yield('code')</div>
        <h1 class="klass-error-title">@yield('title')</h1>
        <p class="klass-error-msg">@yield('message')</p>
        <a href="{{ url('/') }}" class="klass-error-link">Go Home</a>
    </div>
</body>
</html>
