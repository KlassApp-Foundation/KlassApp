# Handoff — emails and public pages (Task B)

**Base:** `KlassApp-Foundation/KlassApp@dbe68419`, read on 2026-09-28.

**Evidence used:**
- the production template export;
- `CATALOG.md`;
- 19 email and page screenshots from staging and local renders.

**How to read the numbers:**
- **Contrast ratios** are computed from hex values; see `concepts/emails/contrast.md`. The catalog's own numbers were treated as indicative only, as you said.
- **Pixel sizes** (48px buttons and so on) are the specified geometry. **They still need measuring** with `getBoundingClientRect` in the rendered previews, and in real clients through Mailpit and Litmus.

**Review pages:**
- `concepts/emails/index.html`: every email at 600 and 375, the plain-text version, and dark-mode and images-off toggles.
- `concepts/public-pages/index.html`: proposed pages at 1280 and 375, next to the staging screenshot.

**Production data:** `guidelines/copy-corrections-2026-09-28.md` is separate. Nothing in this handoff changes data.

---

## B1 · Email shell

### What's wrong today (from the catalog, screenshots and source)
- **Six different looks.** They use Avenir, Arial and DM Sans/Sora fonts, and the #f5f8fa, #f8fafc and #FAFAF5 backgrounds.
- **Logos.** Only the reset code email has a logo, and it's an SVG, which Outlook doesn't render.
- **Contrast failures:**
  - Theme body `#74787E`: 4.44.
  - Footer `#AEAEAE`: 2.08.
  - Header wordmark `#BBBFC3`: 1.73.
  - Reset note `#94A3B8`: 2.56.
- **Buttons.** The notification button `#2AB27B` is 2.71, the invite button `#16A34A` is 3.30 and the database `#008CBA` button is 3.85. They're 42px tall.
- **Credentials in email.** `teacher-invite.blade.php` and `co-admin-invite.blade.php` put the password in the email body.
- **Markdown leak.** The notification email shows `</td></tr>` as text, because Markdown turns indented HTML into a code block. `mailcontent` bodies are indented by 32 spaces, which is the same risk.
- **Plain text and dark mode.** No authored plain-text part, and no `color-scheme` meta.

### Deliverable: the files under `handoff/emails/`, mirroring repo paths
| File | What |
|---|---|
| `resources/views/vendor/mail/html/themes/klassapp.css` | **The theme. It's the single place colours live, all as hex.** Set `'markdown' => ['theme' => 'klassapp']` in `config/mail.php`. |
| `vendor/mail/html/layout.blade.php` | 600px table shell, `color-scheme` meta, Outlook `mso` wrapper, preheader slot, 480px breakpoint, and `prefers-color-scheme` / `[data-ogsc]` dark rules. |
| `vendor/mail/html/header.blade.php` | PNG logo 150×36 (`@2x` source), `alt="KlassApp"`, in a `#FFFFFF` cell. When images are blocked, the alt text shows in bold #15803D on white (5.02). The white plate stays in dark mode so the logo stays legible. |
| `vendor/mail/html/button.blade.php` | 14px + 20px + 14px = **48px** tall, a VML roundrect for Outlook, and full width at 480px and below. Every `color` value resolves to #15803D, except `red`/`error`, which resolve to #B91C1C. |
| `vendor/mail/html/code.blade.php` | **New** `<x-mail::code>` panel for one-time codes: monospace, 32px, #14532D on #F0FDF4 (8.70). |
| `vendor/mail/html/{message,subcopy,footer}.blade.php` | Same slots as today, with no leading indentation, which fixes the `</td>` leak. |
| `emails/mailcontent.blade.php` + `app/Support/MailContent.php` | Frames any database body. It strips leading whitespace, so Markdown never makes code blocks. It restyles the legacy `#008CBA` inline buttons in code, **with no data change**. |
| `emails/mailcontent-text.blade.php` | Authored plain-text part. Pass it with `->text('emails.mailcontent-text')` in each of the 14 Mailables that use `mailcontent`. |
| `emails/registration_otp`, `reset_password_code`, `teacher-invite-link`, `co-admin-invite-link`, `co-admin-promoted` | Rewritten onto the shell. |
| `public/images/email/klassapp-logo-email-2x.png` (and `-3x`) | 360×87 and 540×131. Rasterised from `klassapp-horizontal-light.svg` onto opaque white. |

### Colours (all hex; computed ratios)
| Role | Light | Ratio | Dark | Ratio |
|---|---|---|---|---|
| Page | #F3F0E8 | | #0F172A | |
| Card, 1px #E6E0D4 | #FFFFFF | | #1E293B, border #334155 | |
| Heading | #0F172A | 17.85 | #F8FAFC | 13.98 |
| Body | #334155 | 10.35 | #E2E8F0 | 11.87 |
| Secondary and footer | #475569 | 7.58 / 6.65 on page | #CBD5E1 | 9.85 / 12.02 |
| Link | #15803D | 5.02 | #86EFAC | 10.42 |
| Button | #FFFFFF on #15803D | 5.02 | unchanged | 5.02 |

**Font stack:** `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif`. DM Sans and Sora are never requested.

### Subjects and preheaders (WhatsApp comes first, so these are kept short)
| Email | Subject | Preheader |
|---|---|---|
| Registration OTP | `418205 is your KlassApp code` (`:otp is your KlassApp code`) | It expires in 5 minutes. |
| Reset code | `Your KlassApp reset code: :code` | It expires in 5 minutes. |
| Teacher / co-admin invite | `Join :school on KlassApp` | Set your password to get started. The link expires in 72 hours. |
| Promoted | `You're now Co-Admin at :school` | Sign in with your usual email and password. |
| mailcontent (14) | From the `mailtemplates.subject` column (improvements are in O2 of the copy file) | The first line of the body, passed as `preheader` |
| Notifications (5) | Per notification | The notification's first `line()` |

### Invites are link-only (must-fix)
1. Point `TeacherInviteMail` and `CoAdminInviteMail` at the `-link` views only. **Delete `emails/teacher-invite.blade.php` and `emails/co-admin-invite.blade.php`.**
2. **The expiry is 2 days in the code today** (the screenshots say "2 days from now"). Change the invite `expires_at` to `now()->addHours(72)` wherever the teacher and co-admin invite rows are created. **I haven't found that line.** Search `app/` for the invite models' `expires_at` assignment.
3. The copy says "72 hours" plus the absolute date. It doesn't use `diffForHumans()`, which would render as "3 days".

### Acceptance
- **No passwords:** `grep -rn '\$password' resources/views/emails` → no matches.
- **No CSS variables or SVGs in mail:** `grep -rnE 'var\(|\.svg' resources/views/vendor/mail resources/views/emails` → no matches.
- **Button height:** in the rendered HTML of each Mailable (Mailpit), every `.button` has `getBoundingClientRect().height ≥ 44`. Record the values in the PR.
- **Real clients:** send the 10 preview emails to Mailpit, then to Gmail web and Android, Outlook 365 on Windows, Outlook.com, Apple Mail (light and dark) and iOS Mail. Screenshot each at 600 and 375. Check that:
  - the logo renders, or its alt text shows when images are blocked;
  - there's no `</td>` text anywhere;
  - buttons are full width at 375.
- **Plain text:** every message has a `text/plain` part. Check this in Mailpit's "Text" tab.
- **Contrast:** axe or a computed check on the rendered HTML finds no text below 4.5:1, in light mode or under `prefers-color-scheme: dark`.
- **Hygiene:** delete `resources/views/emails/admin/resetpassword.blade.php`. No Mailable references it, and it prints `$resetlink` unescaped.

### Flags
- **F1 · Reset code length.** The reset page promises "a 6-digit code", but the fixture email shows `7391`. Check the generator in the reset flow, then make the page and the email agree.
- **F2 · Laravel text views.** This repo has `vendor/mail/markdown/`, the pre-Laravel-7 name, where current Laravel uses `vendor/mail/text/`. Confirm which folder the installed version reads before relying on the auto-generated text parts. The shell uses `<x-mail::…>` component syntax (Laravel 9 and later). If the app is older, use the `@component('mail::…')` equivalents.

---

## B2 · Public pages

**Direction:** everything moves onto the existing auth shell (`layouts.auth-preview` + `resources/css/auth-preview.css`: vintage paper, split at 960px, stacked below). No new visual language.

### B2.1 · Auth shell contrast fixes (applies to login, register, reset and every page below)
| Token / rule | Now | On paper-mid / paper-deep | Proposed | Result |
|---|---|---|---|---|
| `--ap-muted` (sub, support, `.ap-back`, `.ap-meta`, hints) | #64748B | **4.18 / 3.82** | #475569 | 6.65 / 6.09 |
| `.ap-divider` "or" | #94A3B8 | **2.25** | #475569 | 6.65 |
| `.ap-link`, `.ap-meta a`, `.ap-checkbox-label a` | #1E6FD9 | **4.26** | #1D4ED8 (keep `--ap-focus` #1E6FD9 for rings only) | 5.88 |
| Placeholder | #94A3B8 | 2.25 | unchanged (a placeholder isn't content) | |

`#64748B` only passes on the paper centre (#FAFAF5, 4.55), and the radial gradient reaches #EBE6DA at the edges.

The grain overlay (`mix-blend-mode: multiply`, 0.22) darkens the paper further. **After the change, sample real pixels behind `.ap-sub` at 375 and 1280.**

**Copy:** "E-Mail Address" becomes "Email" in `reset-request` (the invite page and the database already say "Email").

**44px rule:** the "Remember me" row. The checkbox is 16px, so give `.ap-checkbox-row` `min-height: 44px` and wrap the input in its `<label>`. Every other control already meets 44px (inputs 48, `.ap-submit` 44, password toggle 44, `.ap-link` 44).

### B2.2 · Invite pages (`auth/invite-set-password`, `auth/invite-invalid`): must-fix
- **Root cause:** both extend `layouts.auth-preview` but use `auth-shell`, `auth-card`, `form-input`, `btn btn-primary`, `form-hint` and `alert`. **None of those classes exist in `auth-preview.css`**, which is why the page is unstyled. Both files also have an extra closing `</div>`.
- **Fix:**
  - Rebuild both on `.ap-page > .ap-bg-vintage + .ap-shell > .ap-brand-panel + .ap-form-panel > .ap-card`, following `concepts/public-pages/screens/invite-set-password.html` and `invite-link.html`.
  - Keep every `data-testid` and the three `$reason` branches.
  - Copy per branch:
    - **Expired:** "This invite has expired", with the date and "Invite links last 72 hours".
    - **Claimed:** "This invite has already been used", with Sign in and Forgot password.
    - **Invalid:** "This invite link doesn't work".
  - The read-only email field keeps `readonly` and drops `disabled`, so the address stays readable and selectable.
- **Acceptance:**
  - Screenshots at 375 and 1280 match the concept.
  - The set-password form posts and validates exactly as it does today: the existing feature tests pass.
  - No text below 4.5:1.

### B2.3 · Report-file link (`/whatsapp/report-files/{token}`): must-fix
- **Today:** an expired or tampered signed URL shows the 403 illustrated page with the raw exception text "Invalid signature.". A bad token 404s.
- **Fix:**
  - In the exception handler, render the new view `errors.report-link-expired` (concept `screens/report-link-expired.html`) when an `InvalidSignatureException` hits this route name. Keep the 403 status.
  - Point the 404 case at the same page, reusing its copy.
  - Meta's server-side fetcher still receives the same status codes, so delivery behaviour doesn't change.
- **Acceptance:**
  - A feature test covering an expired signature and a bad token: the response body contains "This report card link has expired" and doesn't contain "Invalid signature".

### B2.4 · Error pages
- **What's live:** 404, 419 and 500 already use `errors-preview.layout` (the vintage shell). **401, 403, 429 and 503 still extend `errors/illustrated-layout.blade.php`.** That layout has:
  - a `#22C55E` button (2.28:1);
  - a `#E2E8F0` numeral (1.18:1; decorative, so add `aria-hidden`);
  - a second visual language.
- **Fix:**
  - Move those four onto `errors-preview.layout`, then delete `illustrated-layout.blade.php`.
  - Also delete `errors/layout.blade.php` if nothing else extends it. Check with `grep -rn "errors.layout" resources/views`.
  - **Don't show `$exception->getMessage()`.** Use fixed human copy per code (403 example in `screens/error-403.html`), because framework messages like "Invalid signature." leak through today.
  - The catalog reports `/preview/errors/503` returns 404. Add the preview route so 503 can be reviewed.
- **Acceptance:**
  - All seven codes render on the vintage shell.
  - Buttons are 44px tall.
  - No text below 4.5:1.
  - `grep -rn "getMessage()" resources/views/errors` → no matches.

### B2.5 · Privacy and Terms (`about.blade.php`)
- **Layout fix (design):** `screens/legal.html`. It replaces the `bg-red-600` band and the Helvetica body, and adds:
  - a Sora/DM Sans paper page;
  - 44px Sign in / Get started buttons, replacing the 30px outline ones;
  - a sticky contents list at 960px and above, hidden below;
  - a 68ch measure, with 16.5px/1.7 `#334155` body text.
  - Both pages share one template.
- **Content: needs an owner and legal review. I haven't rewritten any of it:**
  - **Privacy is legacy GegoSoft boilerplate.** It says "covers every Gegosoft products", claims "We do not use cookies on any of our websites" (the app sets session cookies, so this is false), and mentions Facebook sign-in (login only offers Google).
  - **Terms:** "Platform\'s messaging system" shows a literal backslash, from an escaping bug in the Blade string. Contact is a placeholder, "+256-XXX-XXXXXX".
  - **"Last Updated: September 28, 2026"** matches the capture date. Check it isn't generated from `now()`.
- **Acceptance:**
  - No red band.
  - Every heading has an `id` that the contents list links to.
  - Screenshots at 375 and 1280.
  - The legal copy is unchanged until someone signs it off.

### B2.6 · Public admission form (`/{slug}/admission-form`, `layouts/admission.blade.php`)
- **Today:**
  - broken logo `<img>` when the school has no logo (`$logo->LogoPath` returns nothing);
  - red numbered steps;
  - Next and Reset buttons about 24px tall;
  - native select;
  - Helvetica.
- **Fix, step 1** (`screens/admission-step1.html`):
  - When there's no logo, show the school name and a 48px initials tile. Never an empty `<img>`.
  - A 5-part progress bar in `--ap-primary`, with `aria-current="step"`. Labels are in sentence case, and only the current label shows at 640px and below.
  - Select 48px; buttons 44px.
  - "Reset" becomes "Start over" (secondary); "Next" becomes "Next: Student".
  - Apply the same field and button treatment to steps 2–5 (`pages/admission/*.blade.php`).
- **Needs a decision:**
  - "Standard detail" → "Class". Ugandan schools say class, and the step's only field is already labelled Class.
  - Steps 2–5 contain India-specific fields from the original software: `aadhar_number`, `community`, and half-yearly marks for `tamil` and `english`. Should they stay?
- **Must-fix (not design):**
  - `AdmissionController@store` calls `dd($e->getMessage())` on failure, which dumps exception text to the public visitor.
  - `School::where('slug',$slug)->first()` isn't null-checked in `create`, `list` or `store`, so an unknown slug gives a 500. Use `firstOrFail()`.
- **Acceptance:**
  - With a school that has no logo, no broken-image icon appears.
  - Every control is at least 44px tall.
  - A failed submission shows a form error, not a dump.
  - An unknown slug gives a 404.

### B2.7 · `/{slug}/standardlist`: no page to design
`AdmissionController@list` returns a **JSON array** (standards, transport, blood groups, qualifications) that the admission form uses to fill its options. It isn't a visual page.

It's public and unauthenticated. The data is low sensitivity, but it's listed here so it isn't mistaken for a missing design.

---

**Order:**
1. B1 and B2.1–B2.2, together, because the invite emails link to the invite pages.
2. B2.3 and B2.4.
3. B2.6.
4. B2.5 layout, while the legal content waits for sign-off.
