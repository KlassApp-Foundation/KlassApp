{{-- SPDX-License-Identifier: MIT --}}
{{-- Public follow-along page for the live demo (/demo). Demo school dummy data only. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Follow the KlassApp demo</title>
    <style>
        :root {
            --d-accent: #15803D; --d-accent-dk: #166534; --d-canvas: #FAFAF5; --d-white: #FFFFFF;
            --d-text: #1E293B; --d-text-secondary: #64748B; --d-border: #E2E8F0;
            --d-radius-md: 8px; --d-radius-lg: 12px; --d-focus-ring: 0 0 0 3px rgba(30, 111, 217, 0.25);
            --d-touch-target-min: 44px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--d-canvas); color: var(--d-text); font: 16px/1.5 "DM Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }
        h1 { font-size: 28px; line-height: 1.2; margin: 0 0 8px; }
        h2 { font-size: 20px; margin: 0 0 12px; }
        p { margin: 0 0 12px; }
        .muted { color: var(--d-text-secondary); }
        .card { background: var(--d-white); border: 1px solid var(--d-border); border-radius: var(--d-radius-lg); padding: 20px; margin-top: 20px; }
        .join { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; }
        .qr { width: 220px; height: 220px; flex: none; background: var(--d-white); }
        .qr svg { display: block; width: 100%; height: 100%; }
        .btn { display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; flex-shrink: 0; min-height: var(--d-touch-target-min); padding: 0 16px; border-radius: var(--d-radius-md); background: var(--d-accent); color: #fff; font-weight: 600; text-decoration: none; }
        .btn:hover { background: var(--d-accent-dk); }
        .btn--outline { background: var(--d-white); color: var(--d-accent-dk); border: 1px solid var(--d-border); }
        .btn--outline:hover { background: #F0FDF4; }
        a:focus-visible, .btn:focus-visible { outline: none; box-shadow: var(--d-focus-ring); }
        .roster { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
        .roster li { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid var(--d-border); border-radius: var(--d-radius-md); padding: 10px 12px; min-width: 0; }
        .who { min-width: 0; }
        .who b { display: block; overflow-wrap: anywhere; }
        .code { display: block; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 14px; color: var(--d-text-secondary); }
        .links { margin: 0; padding-left: 20px; }
        .links li { margin: 6px 0; }
        .links a { color: var(--d-accent-dk); font-weight: 600; overflow-wrap: anywhere; }
        .notice { font-weight: 600; }
    </style>
</head>
<body>
<main>
    <h1>Follow the KlassApp demo</h1>
    <p class="muted">Use your own WhatsApp to see what a parent sees. Everything on this page is sample data from a demo school.</p>

    <section class="card" aria-labelledby="join-title">
        <h2 id="join-title">1. Open WhatsApp</h2>
        @if ($joinUrl)
            <div class="join">
                <div class="qr" role="img" aria-label="QR code that opens WhatsApp with the message JOIN" data-testid="demo-qr">{!! $qrSvg !!}</div>
                <div>
                    <p>Scan the code with your phone, or tap the button. WhatsApp opens with the message JOIN ready to send.</p>
                    <a class="btn" href="{{ $joinUrl }}" target="_blank" rel="noopener" data-testid="demo-open-whatsapp">Open WhatsApp</a>
                </div>
            </div>
        @else
            <p class="muted" data-testid="demo-no-number">The demo WhatsApp number is not set up yet.</p>
        @endif
    </section>

    <section class="card" aria-labelledby="bind-title">
        <h2 id="bind-title">2. Pick a student to follow</h2>
        <p>Tap a student. WhatsApp opens with BIND and the student's code; send it, then reply MARKS to see their marks.</p>
        @if ($roster->isEmpty())
            <p class="muted" data-testid="demo-roster-empty">The demo class is not ready yet.</p>
        @else
            <ul class="roster" data-testid="demo-roster">
                @foreach ($roster as $student)
                    <li data-testid="demo-student">
                        <span class="who"><b>{{ $student['first_name'] }}</b><span class="code">{{ $student['code'] }} · {{ $student['class'] }}</span></span>
                        @if ($student['bind_url'])
                            <a class="btn btn--outline" href="{{ $student['bind_url'] }}" target="_blank" rel="noopener" data-testid="demo-bind">Bind to {{ $student['first_name'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card" aria-labelledby="links-title">
        <h2 id="links-title">Links</h2>
        @if ($links !== [])
            <ul class="links" data-testid="demo-links">
                @foreach ($links as $link)
                    <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        @endif
        <p class="notice" data-testid="demo-removal-notice">{{ $removalNotice }}</p>
    </section>
</main>
</body>
</html>
