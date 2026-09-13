---
category: Brand
---

# WhatsAppMark

The official WhatsApp logo mark. Blade equivalent: `<x-brand.whatsapp />` in
`resources/views/components/brand/whatsapp.blade.php`.

```jsx
<WhatsAppMark style={{ width: 24, height: 24 }} />
```

**Do not recolor.** The brand green `#25D366` is baked into the path fills —
`currentColor` has no effect, and overriding the fills misrepresents the mark.

## Sizing

The SVG carries no intrinsic width. Size it from the outside with `style` or a
class; it scales on its `viewBox`.

## Accessibility

`decorative` defaults to `true`, giving `aria-hidden="true"` and no `<title>` —
correct when the mark sits next to a visible "WhatsApp" label. Pass
`decorative={false}` when the mark is the only thing conveying the name; that
renders a `<title>WhatsApp</title>`.

WhatsApp is the primary parent-facing channel in KlassApp, so this mark carries
real product meaning — prefer it over a generic chat glyph.
