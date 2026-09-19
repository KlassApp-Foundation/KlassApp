---
category: Forms
---

# FormGroup

A labelled form control with error and help slots. Blade equivalent:
`<x-form-group>` in `resources/views/components/form-group.blade.php`.

One component covers text, email, number, date, select and textarea — pick with
`type`.

```jsx
<FormGroup label="Subject Name" name="name" type="text" required
           placeholder="e.g. Mathematics" />

<FormGroup label="Class" name="class_id" type="select" required
           placeholder="Select…"
           options={{ '4': 'Senior 1', '5': 'Senior 2' }} />

<FormGroup label="Admission Number" name="admission_no" type="text"
           error="That admission number is already taken." required />
```

## Props

- `label` — renders `<label for>`; the `for`/`id` is `name`, or a slug of the
  label when `name` is empty
- `type`: `text` (default) · `email` · `select` · `textarea` · `number` · `date`
- `options`: `{ value: label }` — select only
- `error` — adds `.ds-form-input-error` and renders `.ds-form-error` below
- `help` — renders `.ds-form-help` below
- `required` — adds the red asterisk (`.ds-form-label-required`) *and* the
  `required` attribute
- `children` — appended inside the wrapper, for a control the component
  doesn't cover

## Note on values

The Blade version reads `old($name, $value)` so it repopulates after a failed
validation round-trip. The React port has no session, so `value` is the
uncontrolled `defaultValue`. Wire it to real state in an app.
