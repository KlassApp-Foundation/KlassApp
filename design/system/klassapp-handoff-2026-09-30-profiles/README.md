# Profile pages: handoff, 2026-09-30

- `HANDOFF.md`: layout, tabs by viewer, empty states, what the current pages get wrong, five PRs with acceptance checks, and open questions.
- `profiles.html`: interactive concept for student (admin, class teacher, bursar), staff (teacher, admin) and parent, at 1280 and 375, with filled, empty-tab and no-class states.
- `account-dashboard.html`: the account card (open, at 1280 and 375) and the admin dashboard with Toshi off or on, for a new, setting-up and set-up school. It also lists every Toshi entry point found.
- `dashboard-v2.html`: the admin dashboard v2 (Toshi off, or on as Early access; three school states; setup banner or sidebar chip) and the sign-up page (email and password, then the email code), all with Lucide icons, at 1280 and 375. **This replaces section B of `account-dashboard.html`.**
- `email-verify.html`: the verification email (code and Confirm email button) at 600 and 375, the updated "Check your email" step, and the link-opened pages: same browser, other device ("Email confirmed"), expired and invalid.
- `icons.html` + `ICONS-HANDOFF.md`: switching to Lucide (ISC licence). Covers sizes, stroke, colour tokens, a map from every current icon to its replacement (sidebar and dashboard first), and six PRs.
- `avatar-initials-spec.md`: the default-avatar spec these pages depend on.
- `design-system/`: the token stylesheets the concept loads.

Designed from `main @ 3cf3025bf5b0`. Contrast ratios are calculated from hex, not browser-measured.
