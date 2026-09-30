A Heroicons v1 outline glyph stroked in `currentColor` — use it for every icon in app chrome.

```jsx
<Icon name="users" size={20} />
<Icon name="chevronDown" size={16} style={{ color: 'var(--d-muted)' }} />
```

**Intentional addition.** The source design system carries icon paths inline inside `KpiCard` and nowhere else; this wrapper exposes the same family (Heroicons v1 outline, 24×24, stroke 2) so sidebars and toolbars don't need hand-drawn SVG. The eight KpiCard glyphs are byte-identical to the source; the chrome glyphs (home, document, upload, logout, search, plus, menu, close, chevrons, tick) are their Heroicons v1 siblings.

## Rules

- Stroke weight is always 2; never fill an icon.
- Icons inherit text colour. Tint by setting `color` on the parent, not by hardcoding a hex.
- Icons are decorative by default (`aria-hidden`). Pass `title` only for icon-only buttons.
- No emoji in product chrome. Emoji do appear inside the Toshi assistant's plan/step cards (`.toshi-plan-step-icon`) — that is the one sanctioned exception.
