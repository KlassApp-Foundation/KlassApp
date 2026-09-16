---
category: Brand
---

# SlackMark

The official Slack logo mark (2019 hash). Blade equivalent:
`<x-brand.slack />` in `resources/views/components/brand/slack.blade.php`.

```jsx
<SlackMark style={{ width: 24, height: 24 }} />
```

**Do not recolor.** The four brand colours — `#E01E5A`, `#36C5F0`, `#2EB67D`,
`#ECB22E` — are baked into the path fills.

## Sizing

No intrinsic width; size it from the outside with `style` or a class.

## Accessibility

`decorative` defaults to `true` (`aria-hidden`, no `<title>`). Pass
`decorative={false}` when the mark alone conveys the name.

Slack is the staff-side channel Toshi orchestrates, alongside
[WhatsAppMark](./WhatsAppMark.md) for parents and
[GoogleDriveMark](./GoogleDriveMark.md) for documents.
