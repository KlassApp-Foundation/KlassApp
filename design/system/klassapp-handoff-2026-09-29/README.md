# KlassApp handoff · 2026-09-29

Start with **HANDOFF.md**. It orders all open work (bugs first) into 22 PRs, each with acceptance checks.

- **Designed from:** `main @ a9013f74b384`.
- **Checked at the end:** `main` was 2 commits past that (the Ugandan admission-fields PR). HANDOFF.md lists the drift.
- `specs/`: earlier specs that HANDOFF.md points to rather than restating.
- `concepts/`: open the HTML files directly in a browser. New in Task C: `concepts/task-c/role-lists.html`, `coverage-map.html`, `landing-motion.html`, `landing-background.html`, and `concepts/public-pages/screens/admission.html`.
- `B-emails-code/`: the email shell, previews and Laravel mail theme from Task B.
- `styles.css`, `tokens/`, `styles/`, `assets/`: the design system the concepts load.

Contrast ratios are calculated from hex unless marked "browser-measured". Frame timing under a 4× CPU throttle has **not** been measured; PR 16 requires a trace.
