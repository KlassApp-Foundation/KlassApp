Checkbox and radio row for any boolean or one-of-many choice in a form; the full row is the 44px touch target.

```jsx
<Checkbox label="Send fee reminders by WhatsApp" name="wa" defaultChecked />
<Checkbox type="radio" name="term" value="2" label="Term 2" />
```

- `type="radio"` — same 18px box and row metrics.
- `disabled` — greys the label to `--d-disabled`.
- Wrap siblings in `.ds-check-group` (stacked) or `.ds-check-group--inline`.
- PROPOSED: no checkbox/radio family exists upstream beyond `.dt-checkbox` in tables.
