---
category: Brand
---

# GoogleDriveMark

The official Google Drive logo mark. Blade equivalent:
`<x-brand.google-drive />` in
`resources/views/components/brand/google-drive.blade.php`.

```jsx
<GoogleDriveMark style={{ width: 24, height: 24 }} />
```

**Do not recolor.** The brand triangle colours are baked into the path fills.

## Sizing

No intrinsic width; size it from the outside with `style` or a class.

## Accessibility

`decorative` defaults to `true` (`aria-hidden`, no `<title>`). Pass
`decorative={false}` when the mark alone conveys the name.

Drive is the document surface Toshi writes to — pair it with
[WhatsAppMark](./WhatsAppMark.md) and [SlackMark](./SlackMark.md) when showing
the connected-tools set.
