# Design inventory: public pages, emails, contrast

Read-only audit. Method: page evidence captured against the deployed staging site because the local Docker/Colima VM would not start (MySQL unavailable); email templates rendered locally from Blade plus the real vendor theme CSS.

## 1. Public pages

| Route | View / source | Layout | Stylesheet | 375 | 1280 | Sub-4.5:1 text | JS errors |
|---|---|---|---|---|---|---|---|
| `/` | welcome (v3 landing) | - | - | 200 | 200 | 36 | 0 |
| `/landing` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/landing2` | landing2.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/features` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/pricing` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/schools` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/contact` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/demo` | landing.blade.php | - | - | 200 | 200 | 40 | 0 |
| `/login` | auth.preview.login | - | - | 200 | 200 | 1 | 0 |
| `/register` | auth.preview.register | - | - | 200 | 200 | 1 | 0 |
| `/password/reset` | auth.preview.reset-request | - | - | 200 | 200 | 0 | 0 |
| `/password/reset-code` | auth.preview.reset-code | - | - | 200 | 200 | 0 | 0 |
| `/password/force-change` | auth.preview.force-change-password | - | - | 200 | 200 | 1 | 0 |
| `/verifyotp` | otp view | - | - | 200 | 200 | 1 | 0 |
| `/privacy-policy` | about.blade.php | - | - | 200 | 200 | 2 | 0 |
| `/terms-of-service` | about.blade.php | - | - | 200 | 200 | 2 | 0 |
| `/preview/login` | auth.preview.login | - | - | 200 | 200 | 2 | 0 |
| `/preview/register` | auth.preview.register | - | - | 200 | 200 | 2 | 0 |
| `/preview/reset-request` | auth.preview.reset-request | - | - | 200 | 200 | 1 | 0 |
| `/preview/reset-code` | auth.preview.reset-code | - | - | 200 | 200 | 1 | 0 |
| `/preview/reset-newpw` | auth.preview.reset-newpw | - | - | 200 | 200 | 1 | 0 |
| `/preview/force-change-password` | auth.preview.force-change-password | - | - | 200 | 200 | 1 | 0 |
| `/preview/errors/404` | errors/404.blade.php | - | - | 200 | 200 | 1 | 0 |
| `/preview/errors/419` | errors/419.blade.php | - | - | 200 | 200 | 2 | 0 |
| `/preview/errors/500` | errors/500.blade.php | - | - | 200 | 200 | 1 | 0 |
| `/preview/errors/503` | errors/503.blade.php | - | - | 404 | 404 | 1 | 0 |
| `/definitely-not-a-real-page-xyz` | errors/404.blade.php | - | - | 404 | 404 | 1 | 0 |
| `/demo-lakeview-junior/admission-form` | admission create view | - | - | 200 | 200 | 0 | 0 |
| `/demo-lakeview-junior/standardlist` | admission list view | - | - | 200 | 200 | 0 | 0 |
| `/whatsapp/report-files/bogus-token` | signed route, 404 without signature | - | - | 404 | 404 | 1 | 0 |
| `/docs` | docs view | - | - | 200 | 200 | 15 | 0 |

Notes: `/` is the live v3 landing; `/landing` is the older landing still routed; the five `scrollTo` routes all render `landing.blade.php`. `/preview/*` routes exist so live `/login`, `/register`, `password/*` and error pages can be reviewed with forced error states. `/docs` is the docs site rendered inside the app shell.

## 2. Emails

Architecture: 21 Mailables and 5 Notifications, but only 6 distinct templates. 14 Mailables render the generic `emails.mailcontent`, whose subject and body come from the `mailtemplates` DB table (15 rows in production), so redesigning those means editing data, not Blade.

| Template | Used by | Subject source | Font | Background | Logo | Button | Sub-4.5:1 | Plaintext password |
|---|---|---|---|---|---|---|---|---|
| `emails.mailcontent` | 14 mails: absences, new user, admission, subscription, calendar, change password, contact, verification, login alert, messages, reminders, reset password, room invite, send mail | mailtemplates.subject (15 rows, production) | Avenir, Helvetica, sans-serif | #f5f8fa | none | n/a | 8 | no (body text from DB) |
| `emails.registration_otp` | RegistrationOtpMail | literal: Your KlassApp verification code | Arial, sans-serif | #f8fafc | none | n/a | 3 | no |
| `emails.reset_password_code` | ResetPasswordCodeMail | literal: Your KlassApp password reset code | DM Sans, Sora | #FAFAF5 | images/klassapp-logo-primary.svg, alt KlassApp | n/a | 2 | no |
| `emails.co-admin-invite` | CoAdminInviteMail | mailtemplates (data-driven) | vendor theme | vendor theme | none | theme green | None | YES: Password: {{ $password }} |
| `emails.co-admin-promoted` | CoAdminInviteMail (promoted variant) | mailtemplates | vendor theme | vendor theme | none | theme green | None | no |
| `emails.teacher-invite` | TeacherInviteMail | mailtemplates | vendor theme | vendor theme | none | 171x42, #16A34A | 13 | YES: Password: {{ $password }} |
| `vendor.notifications.email` | 5 Notifications | per-notification | vendor theme | vendor theme | none | 108x42, #2AB27B | 8 | no |

Contrast detail on the theme: body text `#74787E` on white is about 4.3:1, and footer text `#bbbfc3` is far below 4.5:1. Button green is inconsistent: `#16A34A` from the app's `color => 'green'` versus Laravel's default `#2AB27B` for notifications.

## 3. Contrast, itemised

| Ratio | Where | Text | Foreground | Background | Size | Large-text exception |
|---|---|---|---|---|---|---|
| 1:1 | `/landing2` | 3:54 AM | rgb(255, 255, 255) | rgb(255,255,255) | 11px | no |
| 1:1 | `/landing2` | KlassApp | rgb(255, 255, 255) | rgb(255,255,255) | 14px | no |
| 1:1 | `/privacy-policy` | Privacy Policy | rgb(255, 255, 255) | rgb(255,255,255) | 36px | yes |
| 1:1 | `/privacy-policy` | This is a detailed structure of our privacy  | rgb(255, 255, 255) | rgb(255,255,255) | 16px | no |
| 1:1 | `/terms-of-service` | Last Updated: September 28, 2026 | rgb(255, 255, 255) | rgb(255,255,255) | 16px | no |
| 1:1 | `/terms-of-service` | Terms of Service | rgb(255, 255, 255) | rgb(255,255,255) | 36px | yes |
| 1.18:1 | `/` | KlassApp is open source and self-hostable —  | rgb(226, 232, 240) | rgb(250,250,245) | 14px | no |
| 1.18:1 | `/` | × | rgb(226, 232, 240) | rgb(250,250,245) | 18px | no |
| 1.23:1 | `/` | 01 | rgb(226, 232, 240) | rgb(255,255,255) | 36px | yes |
| 1.23:1 | `/` | 02 | rgb(226, 232, 240) | rgb(255,255,255) | 36px | yes |
| 1.23:1 | `/` | 03 | rgb(226, 232, 240) | rgb(255,255,255) | 36px | yes |
| 1.34:1 | `/` | MIT licensed | rgb(134, 239, 172) | rgb(250,250,245) | 14px | no |
| 1.73:1 | `EMAIL co-admin-invite.html` | KlassApp | rgb(187, 191, 195) | rgb(245,248,250) | 19px | yes |
| 1.73:1 | `EMAIL co-admin-promoted.html` | KlassApp | rgb(187, 191, 195) | rgb(245,248,250) | 19px | yes |
| 1.73:1 | `EMAIL mailcontent.html` | KlassApp | rgb(187, 191, 195) | rgb(245,248,250) | 19px | yes |
| 1.73:1 | `EMAIL teacher-invite.html` | KlassApp | rgb(187, 191, 195) | rgb(245,248,250) | 19px | yes |
| 1.73:1 | `EMAIL vendor-notification-email.html` | KlassApp | rgb(187, 191, 195) | rgb(245,248,250) | 19px | yes |
| 1.94:1 | `/landing2` | Get Started | rgb(71, 85, 105) | rgb(201,100,66) | 13px | no |
| 1.98:1 | `/contact` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/demo` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/features` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/landing` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/landing2` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/pricing` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 1.98:1 | `/schools` | Chat on WhatsApp | rgb(255, 255, 255) | rgb(37,211,102) | 16px | no |
| 2.07:1 | `/contact` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/demo` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/features` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/landing` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/landing2` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/pricing` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.07:1 | `/schools` | ✓ | rgb(34, 197, 94) | rgb(220,252,231) | 18px | no |
| 2.08:1 | `EMAIL co-admin-invite.html` | © 2026 KlassApp. All rights reserved. | rgb(174, 174, 174) | rgb(245,248,250) | 12px | no |
| 2.08:1 | `EMAIL co-admin-promoted.html` | © 2026 KlassApp. All rights reserved. | rgb(174, 174, 174) | rgb(245,248,250) | 12px | no |
| 2.08:1 | `EMAIL mailcontent.html` | © 2026 KlassApp. All rights reserved. | rgb(174, 174, 174) | rgb(245,248,250) | 12px | no |
| 2.08:1 | `EMAIL teacher-invite.html` | © 2026 KlassApp. All rights reserved. | rgb(174, 174, 174) | rgb(245,248,250) | 12px | no |
| 2.08:1 | `EMAIL vendor-notification-email.html` | © 2026 KlassApp. All rights reserved. | rgb(174, 174, 174) | rgb(245,248,250) | 12px | no |
| 2.18:1 | `/` | 6 active | rgb(34, 197, 94) | rgb(248,250,252) | 11px | no |
| 2.18:1 | `/` | Protocol Cores | rgb(34, 197, 94) | rgb(248,250,252) | 12px | no |
| 2.18:1 | `/contact` | $8.2K | rgb(34, 197, 94) | rgb(240,253,244) | 18px | no |

Total sub-4.5:1 items catalogued: 409 across 34 surfaces.

## 4. Other findings worth a designer's attention

- Emails have no logo at all except `reset_password_code`, and that one uses an SVG (`images/klassapp-logo-primary.svg`), which Outlook does not render; it needs a PNG with explicit width/height.
- Fonts: the vendor theme asks for `Avenir, Helvetica, sans-serif` (Avenir is not available in most mail clients, so it falls back to Helvetica); `reset_password_code` asks for `DM Sans`/`Sora`, neither of which will load in email.
- Background fallbacks: the vendor theme sets `#f5f8fa` on body and `#FFFFFF` on the container, so dark-mode clients will light-invert; the two bespoke templates use `#f8fafc` and `#FAFAF5` with no `color-scheme` meta.
- Button sizing is inconsistent and small by touch standards on mobile: 171x42 and 108x42 from the theme, and no buttons at all in the two bespoke templates.
- `resources/views/emails/admin/resetpassword.blade.php` exists but no Mailable references it, and it interpolates `{{ $resetlink }}` unescaped in the footer.
- Plain-text parts: Laravel derives a text version automatically, but no template or Notification defines an explicit one, so the text alternative is generated from HTML rather than authored.

## 5. Non-page public surface noticed (out of scope, worth a look)

- Unauthenticated API endpoints that return school or student data by id: `/api/schools/list`, `/api/whatsapp/student/{studentId}/grades`, `/api/whatsapp/student/{studentId}/attendance`, `/api/whatsapp/fees/{studentId}/balance`, `/api/whatsapp/student/{studentId}/report`, `/api/events/show/details/{id}`.
- Ops endpoints exposed without auth: `/tinker` (web-tinker), `/cache-clear`, `/checksms`.

## 6. Method caveats (read before acting on section 3)

- **Contrast numbers are indicative, not final.** The measurement parsed `rgb()`/`rgba()` only. This project uses Tailwind v4, which emits `oklch()` colour values, so any element whose background is declared in `oklch()` was treated as white and produced a false `1:1`. Verified example: `/privacy-policy` and `/terms-of-service` show white-on-red hero bands (`bg-red-600`) with white text, which is a false positive, not a defect. A reliable pass needs an `oklch()`/`color()` parser and handling for background images and gradients.
- **Background images and gradients are not accounted for**, so any contrast result on top of an image or gradient should be re-checked against the actual pixels.
- Page evidence was captured against the **deployed staging site**, not local, because the local Docker/Colima VM would not start and MySQL was unreachable. Staging runs the same code as main (`4a1f4ecf`).
- Email screenshots were rendered from the Blade templates plus the real vendor theme CSS, injected the way Laravel inlines it at send time. The pipeline that actually inlines it was not exercised, so spacing may differ slightly from a real inbox.
- `/docs` renders a documentation site inside the app shell, which is why it has the highest sub-4.5:1 count; it is a vendored theme rather than KlassApp's own design.
