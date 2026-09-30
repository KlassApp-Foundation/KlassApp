The official WhatsApp mark — KlassApp's core channel; use it wherever a message, link or parent contact is WhatsApp-delivered.

```jsx
<span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
  <WhatsAppMark style={{ width: 18, height: 18 }} /> Linked to WhatsApp
</span>
<WhatsAppMark decorative={false} style={{ width: 32, height: 32 }} />
```

## Rules

- The SVG has no intrinsic size — always set width/height (16–20px inline, 24–32px in a tool chip, 40px+ in a connect card).
- **Never recolour.** #25D366 and the white glyph are WhatsApp's, not ours. The same applies to `SlackMark` and `GoogleDriveMark`.
- `decorative` defaults to true (`aria-hidden`). Pass `decorative={false}` only when the mark is the sole label for the control.
- Don't substitute a generic chat bubble icon for WhatsApp. Conversely, `KpiCard icon="whatsapp"` is a *generic* outline bubble — that one is a metric glyph, not the brand.
