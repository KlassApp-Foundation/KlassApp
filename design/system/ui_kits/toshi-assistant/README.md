# UI kit — Toshi assistant

Recreation of KlassApp's in-app AI assistant, built from the `toshi-*` class
vocabulary in the app stylesheet (`styles/classes.css`).

- `index.html` — the assistant docked beside a dashboard (desktop layout).
- `ToshiPanel.jsx` — header, message area, suggestion chips, plan card, tool-confirmation card, composer.
- `ToshiApp.jsx` — the host page and the collapsed `.toshi-pill`.

## Try it

Click a suggestion chip (or type and press Enter). A fee request produces a plan
card and then the confirmation card; **Yes** marks it done, **No** puts the card
into its cancelled state (amber rail, struck-through values).

## Fidelity notes

The app stylesheet owns colours and structure for every `toshi-*` class; the
message bubbles themselves live in Blade markup that was not in the bundle, so
they are composed minimally from `--toshi-user-bubble` / `--toshi-bot-bubble`
and flagged here rather than invented in detail.

Layout rules taken from the source: the panel is 400×600 with a 16px radius and
`0 8px 40px rgba(0,0,0,.12)`; at ≥1280px it sits statically inside the flex
layout; at ≤640px it goes fixed full-screen with a sticky composer.
