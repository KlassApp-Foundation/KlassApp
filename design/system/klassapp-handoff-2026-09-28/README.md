# KlassApp handoff — 2026-09-28 (base `dbe68419`)

Read in this order:
1. `A-handoff-app-resync.md`: Task A. App component library resync in 5 phases, each with acceptance checks. It includes the `<x-empty-state>` recommendation and the table and badge tokens.
2. `B-handoff-emails-public.md`: Task B. Email shell and public pages.
3. `B-copy-corrections-production.md`: proposed production copy fixes (C1–C3 required, O1–O5 optional). **Not applied. Nothing in this bundle writes data.**

Code and previews:
- `B-emails-code/`: drop-in files that mirror repo paths (mail theme CSS, `vendor/mail/html` components, email views, `App\Support\MailContent`, logo PNGs).
- `concepts/emails/index.html`: every email at 600 and 375, the plain-text versions, and dark-mode and images-off toggles. `contrast.md` has the computed ratios.
- `concepts/public-pages/index.html`: proposed public pages at 1280 and 375. `screens/*.html` open standalone, and `pp.css` mirrors `auth-preview.css` with the contrast fixes marked `FIX`.

**Open these over a local server** (for example `npx serve .`). The review pages load their data with `fetch`, and that doesn't work from `file://`.

`concepts/public-pages/index.html` also shows the staging screenshots in its "Now" column. They weren't copied into this bundle, so those images appear broken here.

Contrast values are computed from hex values. Pixel sizes are specified geometry, so measure them in the rendered output before merging.
