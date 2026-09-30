Label + control + error/help in one block — the only form primitive KlassApp ships; every field in every wizard and settings screen is one of these.

```jsx
<FormGroup label="Student name" name="student_name" required placeholder="e.g. Nakato Sarah" />
<FormGroup label="Class" name="class" type="select" options={{ s1: 'Senior 1', s2: 'Senior 2' }} placeholder="Choose a class" />
<FormGroup label="Amount" name="amount" type="number" error="Enter an amount greater than zero" />
```

## Notes

- `required` adds a red asterisk after the label, not the word "required".
- `error` swaps the border to red and prints the message below in 0.78rem red; `help` prints the same size in `--d-muted`. Show one or the other, not both.
- The control is uncontrolled (`defaultValue`) — mirroring the Blade original, which re-renders from `old()`.
- `select` gets the chevron background image; don't add your own arrow.
- There is no standalone `Input`, `Select`, `Checkbox` or `Switch` component. Raw `.ds-input` / `.dt-checkbox` classes exist for the cases FormGroup can't cover.
