# Handoff: profile pages (student, staff, parent), 2026-09-30

- **Designed from:** `main @ 3cf3025bf5b0`. Before starting, run `git log --oneline 3cf3025bf5b0..main -- resources/views/admin/{member,teacher,staff,parent} resources/assets/js/components/{student,teacher,parent}/profile app/Services/RosterScopeService.php`.
- **Concept:** `concepts/profiles/index.html`. It has a switch for profile type, viewer and data (filled, empty tabs, no class), shown at 1280 and 375.
- **Depends on:** `<x-profile-photo>` with the default avatar (`handoff-2026-09-30-avatar-initials.md`), the Task C column matrix, and the resync phases 1 (buttons/forms) and 2 (unified dialogs).
- **Measured in the concept:** no horizontal overflow at 1280 or 375, and every button, link and tab is ≥ 44px tall, via `getBoundingClientRect`.
- **Not measured:** contrast (calculated from hex), and the real app.

## Shared layout
1. **Top bar:**
   - a 44px back button, labelled "Back to {Students|Staff|Parents}";
   - a breadcrumb.
2. **Header card:**
   - `<x-profile-photo size="lg">` (128) on desktop and `size="md">` (64) below 768px;
   - the name from `userprofiles.firstname` + `lastname`, in Sora 600, 28/21px;
   - a meta row, then the actions.
   - **Below 768px** the actions drop to a full-width row.
3. **Tabs:** 44px tall, 14.5px, a 3px `--d-accent` underline on the active tab, scrolling sideways on mobile.
4. **Tab panels:** white cards, 1px `--d-border`, 14px radius.
5. **Status badges:**

   | Status | Background | Text |
   |---|---|---|
   | Active | #DCFCE7 | #14532D |
   | Inactive | #F1F5F9 | #334155 |
   | Exited | #FEF3C7 | #78350F |

6. **Money owed** is #92400E, 7.09:1 on white.
7. **Contact links:**
   - phone as a `tel:` link, email as `mailto:`, each ≥ 44px tall;
   - "on WhatsApp" is shown when the number is flagged as a WhatsApp number.
8. **Empty states:** every tab has one: a title, one line naming what's missing, and a next action only when the viewer can take it (the copy is in the concept's `EMPTY` table). Never "No data available".

## Student profile
**Header:** photo, name, class, admission number and status.
- **No class:** show "No class" in amber (#78350F). Only an admin also gets an **Assign class** link.
- **Admission number:** `admission_no`, never `$user->id`.

**Tabs by viewer.** The first tab is the default.

| Viewer | Tabs, in order | Header actions |
|---|---|---|
| Admin (3) | Overview, Attendance, Marks, Fees, Health, Documents, Notes, Library | Edit, More (Deactivate, Exit, Reset password, Log in as student, Delete) |
| Class teacher (5, and the stream's `class_teacher_id`) | **Attendance**, Marks, Overview, Discipline, Health | Message parent |
| Bursar (Accountant 11) | **Fees**, Overview | Send fee reminder |

- **Subject teacher** (5, not the class teacher): reuse the Task C row. Marks for their own subject only, no photo and no admission number. It isn't drawn in this concept.
- **Scope line:** class-teacher and bursar views get a one-line note under the header saying what isn't shown.
- **Absent, not disabled:** fields and tabs a role never sees are left out of the markup entirely.
  - **Bursar:** never receives Attendance, Marks, Health, Library, Discipline, date of birth, sex or LIN.
  - **Class teacher (decided 2026-09-30):** fee **status only**, "Cleared" or "Not cleared", on request (a **Show fee status** button on Overview, calling a separate endpoint). **Never amounts**: the endpoint returns a boolean, not a balance.
- **Health:** always "on request". The tab shows a **Show health notes** button that calls a separate, policy-gated, logged endpoint. The notes aren't in the profile payload.
- **Documents (decided):** its own **admin-only** tab. Birth certificates, baptism cards and passport photos uploaded on the admission form land here.
  - Empty state: "No documents uploaded" with **Upload document**.
- **Notes (decided):** stays, **admin-only**. Every view is logged, like health notes. The notes load from a separate endpoint when the tab opens and are never in the profile payload.
- **Dropped from the student profile:** Timeline, Siblings, Leave History and **Bank Details**. Parents are shown on Overview.

## Staff profile (teacher and admin)
**Header:** photo, name, role badge ("Teacher", "School admin", "Librarian"…), "Class teacher of P.5 Blue" when it applies, and status. Actions are Edit and More.

**Overview:** two cards.
- **Classes and subjects:** one row per class, with "Class teacher" tagged. Otherwise the subjects taught in that class.
- **Contact:** phone and email.

**Tabs:** Overview, Classes, Attendance, Leave, Documents.
- **Admin accounts:** the same page. Classes shows the empty state "Not teaching any classes", with **Assign classes**.
- **Bank details (decided):** visible only to the **bursar (Accountant 11)** and the school's **main admin** (the SchoolAdmin who owns the school; not sub-admins).
  - **Masked:** shown as `•••• •••• 4821`, with only the last 4 digits sent in the payload.
  - **Reveal:** a **Show full number** button calls a separate endpoint that writes an audit row (who, whose, when) and returns the number for that one view. No reveal is kept in the page after reload.
  - **Everyone else:** the section is absent.

## Parent profile
**Header:** photo, name, the "Parent" role badge, phone, a WhatsApp flag if set, and status.

**The first tab, and the default, is Children.**
- **Each child row:** an sm (40) avatar, the name, the class and the balance ("UGX 180,000 due" or "Fees cleared").
- **Quick links:** **Report card** and **Fees** buttons for each child.
- **Empty:** "No children linked yet", with **Link a child** for admins.

**Other tabs:** Overview (contact, district) and Messages (recent WhatsApp sends).

- **Parent viewing their own profile:** same Children card, scoped to their own children.
- **Notes:** an admin-only tab, with each view logged (decided). **Feedbacks** drops out of the default set.

## What the current pages get wrong
Read at `3cf3025bf5b0`. **I did not re-read `admin/staff/show` in this pass.**

1. **Database id shown:** the header prints `ID: {{ $user->id }}` (the database id) on the student, teacher and parent pages.
2. **Missing header fields:** the student header has no class and no status, so there's no "No class" state.
3. **Epoch dates:** a null date of birth prints `01-01-1970`, via `date('d-m-Y', strtotime(null))`.
4. **Crashes:** `$user->userprofile->gender`, `->address` and `->alternate_no` have no `optional()`, so they crash when there's no profile row.
5. **Three name sources:** `displayName`, `FullName` and `users.name`.
6. **Sex:** shown as "Boy/Girl" on students (blank for any other value), and raw lowercase on teachers.
7. **Student tabs:**
   - Every viewer gets the same 13 tabs, including **Bank Details** on a child's profile.
   - It always opens on Profile.
   - Only Notes is gated, and only client-side (`mode=='admin'`).
   - **I haven't verified whether each tab's API endpoint scopes by role.**
8. **Medical History** is an ordinary tab for every viewer: it isn't on request and isn't logged.
9. **Delete without confirmation:** student and teacher Delete is a bare submit. The parent confirm reads "Do you want to change the Delete Parent ?".
10. **Stray text:** the parent page renders the literal text `x items-center mr-2" id="delete">` from broken markup.
11. **Small targets:** Edit/Delete are about 20px tall, and the actions menu is 24px.
12. **Contact links:** phone and email use `href="#"`, not `tel:`/`mailto:`.
13. **Old alerts:** SweetAlert v1 from an unpinned unpkg URL, `#3085d6`/`#d33` buttons, and a second "Cancelled" alert after Cancel.
14. **Tablet overflow:** `md:w-1/5` (about 144px at 768) around a 192px photo, on the student and parent pages.
15. **Inconsistent headers and colours:** `ds-page-head` vs `admin-h1`, plus five non-token action colours.
16. **Duplicate ids:** `id="status"` and `id="Capa_1"`.
17. **Parent page:** children are hidden in a tab, with no report card or fee links.
18. **Teacher page:** classes and subjects are only in a tab.

## PRs (in order)
### P1 · `fix(profiles): scope tab payloads by role` (bugs first)
- **Server gating:** gate each student-profile tab endpoint through a policy that uses `RosterScopeService`, following the Task C matrix. A "never" field isn't selected at all. Health moves to its own on-request, logged endpoint.
- **Accept:**
  - A feature test per viewer (admin, class teacher, subject teacher, accountant, parent) asserting the JSON keys each endpoint returns.
  - The accountant gets 403 on attendance, marks and health.
  - The class-teacher fee-status endpoint returns `{cleared: bool}` only; the test asserts no amount key.
  - Health, notes and bank-detail reveals each write an audit row.
  - Bank details return last-4 only unless a reveal is requested by the accountant or the main admin; everyone else gets 403.

### P2 · `fix(profiles): header data bugs`
- **Admission number:** replace `ID: $user->id` with the admission number (students), or drop it (staff, parents).
- **Dates:** null-safe dates, where empty shows "Not given" and never 1970.
- **Crashes:** `optional()`/`?->` on every `userprofile` read.
- **Names:** from `firstname`/`lastname`.
- **Sex:** one wording across pages: "Male", "Female", or the stored value title-cased.
- **Parent page:** remove the stray markup.
- **Duplicate ids:** fix the repeated ids.
- **Accept:**
  - A user with no profile row renders without an error.
  - A null date of birth shows "Not given".
  - The parent page contains no stray text.

### P3 · `feat(profiles): student profile redesign`
- **Scope:** header, role-ordered tabs with the default per viewer, the no-class state with Assign class, empty states, the More menu and a 44px back button.
- **Deletes and status changes** go through the unified confirm dialog (resync phase 2).
- **Accept:**
  - Screenshots at 1280 and 375 for admin, class teacher and bursar.
  - Each viewer lands on its default tab.
  - The bursar's DOM contains no Attendance, Marks or Health.
  - Every target is ≥ 44px.
  - No horizontal overflow at 375 or 768.

### P4 · `feat(profiles): staff profile redesign`
- **Scope:** header with role, class-teacher line, classes and subjects, contact, and tabs.
- **Page split:** covers `admin/teacher/show` and `admin/staff/show`. **Read `staff/show` first**, then decide whether they merge into one view with a role switch.
- **Accept:** screenshots for a teacher with classes and for an admin with none (empty state).

### P5 · `feat(profiles): parent profile redesign`
- **Scope:** Children first, with Report card and Fees links for each child, and the empty state with Link a child.
- **Link targets:** the existing report card and fee routes for that child, scoped so a parent only reaches their own children.
- **Accept:** screenshots for 2 children and for 0; the links resolve.

### Every PR
- **Regressions:** the mobile menu opens exactly once, the Toshi split-layout still collapses and resizes, the sidebar footer still works, and the design-system feature tests pass.
- **Screenshots:** before/after at 1280 and 375.
- **knowledge.md:** stamped with the branch-point SHA.

## Decisions (2026-09-30, user)
1. **Class teacher, fees:** status only ("Cleared" / "Not cleared"), on request, never amounts.
2. **Student Documents:** its own admin tab, because admission uploads land there.
3. **Staff bank details:** bursar and the main admin only; masked to the last 4 digits, and a reveal is logged.
4. **Notes:** stays, admin-only, with each view logged like health notes.

---

# Part A · Account card (sidebar footer menu)
**Concept:** `concepts/profiles/account-dashboard.html`, section A.
**Replaces:** `layouts/partials/profile-dropdown.blade.php` (the `.profile-click` / `.user-dtl` markup) where it's included from `sidebar-footer`.

**What's wrong today** (read at `3cf3025bf5b0`):
- **Name source:** the name is `FullName` if `firstname` is set, else **`users.name`, the login handle**.
- **Avatar:** the trigger is a 32px round photo with no name next to it.
- **Menu:** the items are `Change Password`, `Edit Profile` and `Logout`, in Title Case.
- **Log out:** fires from an inline `onclick` on an `<a>`.

**Design:**
- **Trigger** (the sidebar footer row, 56px tall):
  - a 40px `<x-profile-photo size="sm">` (square, 12px radius);
  - the full name (`firstname lastname`, stored case) over the email, both single-line with `text-overflow: ellipsis` on the **right**;
  - a chevron.
  - The row is a `<button aria-haspopup="menu" aria-expanded aria-controls>`.
- **Card:** opens **above** the trigger, full sidebar width minus 8px, white, 1px `--d-border-strong`, 14px radius, with shadow `0 12px 32px rgba(15,23,42,.14)`.
  - **Header:** the avatar, name and email again, each with a `title` holding the full value.
  - **Rows:** **Change password**, **Edit profile**, **Settings**, a divider, then **Log out**. Each row is 44px, with a 20px icon and 14.5px/600 text, in sentence case.
  - **Icons:** the stroke icons already in the partial.
  - **Log out:** a `<button>` in the existing logout form, text #B91C1C (6.47:1 on white).
  - **Stop impersonating** stays, conditional, above the divider.
- **Truncation:** `white-space: nowrap; overflow: hidden; text-overflow: ellipsis; direction: ltr` on both lines. **Never** `direction: rtl` or left-side clipping: the start of the name and email must always be visible.
- **Keyboard:**
  - Enter or Space on the trigger opens the menu and focuses the first item.
  - ↑/↓ move between items, wrapping at the ends.
  - Esc closes the menu and returns focus to the trigger.
  - Tab out closes it.
  - Items are `role="menuitem"` inside `role="menu"`.
- **Closing:** a click outside (`pointerdown` on the document outside the card and trigger), choosing an item, Esc, or route navigation.
- **Mobile (below 768px):** the same card at the bottom of the menu drawer (`#res_sidebar`), full drawer width minus 8px. It opens upward from the drawer footer.
- **Collapsed rail** (`body.sidebar-collapsed`): the trigger shrinks to the 40px avatar alone, and the card opens to the right of the rail, 280px wide.

**Measured in the concept:**
- every row is 44px, and the trigger is 56px;
- the name and email truncate with an ellipsis and the first character stays visible;
- the card stays inside the viewport at 1280 and 375.

---

# Part B · Admin dashboard v2 (Toshi off by default)
**Concept:** `concepts/profiles/dashboard-v2.html`. It has switches for Toshi off/on, three school states (new with 0 students, setting up, set up), and banner showing/dismissed.

**Replaces:** the v1 layout in `account-dashboard.html` section B, which is kept for its "what's wrong today" notes (read at `3cf3025bf5b0`):
- `dashboardGreeting()` prints the stored firstname, so capitals print as capitals, and it falls back to `users.name`.
- `$openToshiOnboarding` is true whenever setup is incomplete.
- Two different step counts.
- No academic-year selector below 768px.
- No 0-students state.

**Icons:** Lucide, per `handoff-2026-10-01-icons.md`.

**Pattern source:** the layout patterns come from the TinyFish console (it uses shadcn/ui-style patterns; shadcn/ui is MIT-licensed, with a Vue port):
- small "Welcome back" over a large title;
- one dismissible banner;
- cards with a side snapshot;
- grouped sidebar labels;
- a sidebar progress chip;
- a split sign-up page.

**Nothing of the TinyFish brand is used:** no colours, type, logo, illustrations or copy.

## Layout at 1280
- **Shell:**
  - **Sidebar:** 260px on the paper canvas (`--d-canvas` #FAFAF5), with no border.
  - **Content:** a white panel inset 12px, with a 1px `--d-border` and a 16px radius.
- **Header** (inside the panel):
  - **Greeting:** "Welcome back, {Firstname}" at 15px in `--d-text-secondary` (#64748B, 4.76:1 on white).
  - **Title:** the **school name** in Sora 600 32px, as the page `<h1>`.
  - **On the right:** the **Academic year** label above a 44px select of at least 200px, with the current year selected and marked "(current)".
  - **Name rules:** as before: `userprofiles.firstname`, first word, title-cased. With no firstname the greeting is just "Welcome back".
- **Setup banner:** shown while setup is incomplete and not dismissed.
  - **Style:** tinted #F0FDF4, 1px #BBF7D0 border, 14px radius.
  - **Title:** "Finish setting up {school}".
  - **Progress:** "**{done} of {total} steps done.** Next: {step}.", then a 6px progress bar (`role="progressbar"`).
  - **Buttons:** **Continue setup →** (primary). With Toshi on, **Set up with Toshi** as a secondary button.
  - **Close:** a 44px ✕ labelled "Hide setup banner. Progress stays in the sidebar."
  - **One banner only:** it replaces the product-demo carousel and the old `setup-banner` partial.
- **Two columns** (`minmax(0,1fr) 360px`, 28px gap):
  - **Left, Quick actions:** a 2-column card grid with the five tiles. The last odd tile spans both columns.
    - **Tile:** a 40px icon square, a 16px title and one line of helper text.
    - **Background:** white with a faint dot grid (#E2E8F0, 14px).
    - **Missing prerequisite:** the helper line names it in #78350F and the tile links to that setup step.
  - **Right, School snapshot:** one bordered card, one row per figure:
    - label and sub-line on the left, the value in Sora 22px on the right;
    - the rows are **Students**, **Fees collected** (this term, "of UGX … billed"), **Attendance today** ("386 of 412 marked present") and **Report cards sent** (last term, on WhatsApp).
- **Empty states** everywhere in the dashboard follow one pattern:
  - a 48px outlined icon;
  - **one line** that names what's missing;
  - **one action**.

  For example, the snapshot with 0 students shows a people icon, "No students yet. Add them to see this snapshot." and **Add students**.

## Setup chip (after dismissal)
- **Where:** once the banner is dismissed, setup progress moves to a chip at the **bottom of the sidebar**, directly above the account trigger.
- **Contents:**
  - a white card with a 1px border, 12px radius and at least 44px tall;
  - "**Finish setup**" with a "**3/7**" pill (#DCFCE7 / #14532D);
  - "Next: {step}";
  - a 4px bar.
- **Behaviour:**
  - The whole chip links to `/admin/onboarding/wizard` at the next step, with `aria-label="Finish setup, 3 of 7 steps done"`.
  - It disappears when setup is complete.
- **Remembering the dismissal:** store it per user and school, in `user_preferences` or similar, **not** localStorage, so it holds across devices. The banner never comes back once dismissed; the chip carries the progress.
- **One count:** banner and chip both read `OnboardingStepsService` (`total`, `done`, `next`).

## Sidebar
- **Groups:** grouped sections with small uppercase labels (11.5px, 700, 0.08em tracking, `--d-text-secondary`):
  - (no label) Dashboard
  - **People:** Students, Staff, Parents
  - **Academics:** Classes, Attendance, Exams and marks, Report cards
  - **Money:** Fees
  - **Messages:** WhatsApp
  - **School:** Settings, Help
- **Map, don't reorder the product:** the real items come from `config/navigation.php`. Map each to a group, and keep any items not shown here in the nearest group.
- **Items:** 44px, a 20px stroke icon, 14.5px/500. The active item is **#E8EFE7** with 700 text and `aria-current="page"`.
- **"New" badge:**
  - **Style:** a #DCFCE7 / #14532D pill, 11.5px/700, right-aligned in the item.
  - **Data:** a `new_until` date per nav item in config. It shows while `now() < new_until`, and never on more than 2 items at once.
  - **Example:** Report cards is shown with the badge in the concept.
- **Bottom:** the setup chip (when dismissed and incomplete), then the **account trigger and card** from Part A.
- **Mobile (375):**
  - **Top bar:** 56px with menu, logo and search.
  - **Drawer:** the menu drawer holds the same groups, the chip and the account trigger.
  - **Content:** the white panel goes full-bleed, with no inset or radius.
  - **Stacking:** the header stacks and the year selector is full width. Then the banner, then the quick actions in 1 column (icon left, text right), then the snapshot.

## Toshi off / on
**Off (the soft-launch default, with no AI key):** nothing on the page mentions Toshi:
- no "Set up with Toshi";
- no dock or panel;
- no `toshi-ui.css`;
- no product-demo carousel.

**On, as "Early access" (decided 2026-10-01):** Toshi is on for selected schools only, labelled **Early access** wherever it appears.
- **Banner:** a secondary **Set up with Toshi** button containing an "Early access" pill.
- **Dock (1280):** the collapsed 44px dock on the panel's right edge holds the K button and, under it, a vertical "Early access" pill.
- **Dock (375):** a floating 44px button at the bottom right, showing the K and an "Early access" pill.
- **Pill:** #FEF3C7 background, #78350F text, 11.5px/700 (9.4:1, calculated from hex).
- **Screen readers:** the button's `aria-label` is "Open Toshi, early access".
- **The Toshi panel header** (not drawn here) shows the same pill next to the name.
- **Never auto-opens** (see Part C, row 3).
- **Who has it:** a per-school flag, e.g. `schools.toshi_early_access`, is checked inside `Toshi::enabled()`. The label shows whenever Toshi is enabled, until the flag is retired.

## Contrast (calculated from hex)
| Text | Background | Ratio |
|---|---|---|
| #0F172A body, titles | white | 17.9:1 |
| #64748B "Welcome back", group labels, sub-lines | white | 4.76:1 |
| #64748B group labels | #FAFAF5 | 4.55:1 |
| #78350F prerequisite line | white | 9.4:1 |
| #14532D "New" / "3/7" pills | #DCFCE7 | 7.9:1 |
| #0F172A body | #F0FDF4 banner | 17.1:1 |

# Part C · Toshi entry points
**The switch:** one server-side check, e.g. `App\Support\Toshi::enabled(?User $user): bool`. It's true only when **all** of these hold:
- an AI key is configured (`config('ai.providers.openai-compatible.key')` non-empty);
- `toshi.sdk_v2_enabled`;
- the school's `toshi_enabled` (via the existing `ToshiSdkV2Service::isAvailable`).

Expose it as a Blade `@toshi … @endtoshi` directive and a view-shared `$toshiEnabled`.

**Keyword router:** `config/toshi.php` says that with the LLM off, Toshi "falls back to the keyword router". The panel would still render and answer from keywords. **With this switch off, the panel doesn't render at all**; the keyword fallback is for rate limits, not for "no key".

| # | Entry point | Where (at `3cf3025bf5b0`) | What to hide |
|---|---|---|---|
| 1 | Panel and ▶ toggle | `layouts/partials/toshi-embed.blade.php` | The whole partial: `@livewire('agent-toshi')`, `#toshi-toggle`, split and resize script |
| 2 | Pre-paint and CSS | `layouts/app.blade.php` L22, L53, L74; `layouts/superadmin-app.blade.php` L22, L34, L52 | `toshi-ui.css` link, `toshi-prepaint`, the embed include (otherwise `#app` keeps a right margin) |
| 3 | Dashboard auto-open | `admin/dashboard/dashboard.blade.php` L411–416; `Admin/DashboardController` L101 | Force `$openToshiOnboarding = false` when off. When on, stop tying it to `$setupIncomplete`. |
| 4 | Registration hand-off | `Auth/RegisterController` (`open_toshi_onboarding` session flag) | Don't set it when off |
| 5 | Setup banner | `partials/setup-banner.blade.php` L31, L40–45 | Replaced by the setup card (Part B) |
| 6 | Product-demo carousel | `partials/empty-state-product-demo.blade.php` (dashboard L20) and `public/js/empty-state-product-demo.js` | Removed in both states |
| 7 | Onboarding reminder | `partials/onboarding-reminder.blade.php` ("Open Toshi", clicks `#toshi-pill`) | **Delete (decided).** Run `grep -rn "onboarding-reminder" resources/ app/` first. If there are no includes, delete the file in B0. If one is found, replace it with the setup chip and delete the file. |
| 8 | Integrations settings | `admin/settings/integrations.blade.php` L8, L48, L85–87 | The page and its settings link |
| 9 | Toshi activity | `admin/toshi_activity/show.blade.php`, route `admin.toshi.activity` | The page, route and any link. **The link's location was not found.** |
| 10 | SiteAdmin Toshi settings | `config/navigation.php` L106 (`superadmin/toshi*`) | Platform scope: follows `toshi.platform_gate`, not the school switch |
| 11 | WhatsApp free-form | `config/toshi.php` `whatsapp_channel_enabled` | Backend: make it depend on the same switch so parents never get a Toshi reply |
| 12 | Manual wizard | `admin/onboarding/wizard.blade.php` L5–9 | Already hides Toshi; nothing to add |
| 13 | Landing page | `partials/landing-toshi-tower.blade.php` and the landing sections | Marketing: **not** covered by this switch; decide separately |

**Coverage:** the code search stopped early in `resources/views`, `app/` and `resources/assets/js`, so the table is a **minimum**. Before PR B1, run `grep -rniE "toshi" resources/ app/ public/js/ config/ routes/` and add every hit, especially the Vue components, `command-palette` and any empty states that suggest "Ask Toshi".

# Part D · Sign-up page
**Concept:** `concepts/profiles/dashboard-v2.html`, section "Sign-up".

**Replaces:** the register view (`auth/register`), which I haven't re-read in this pass. **The current flow stays for now (decided 2026-10-01):** email and password, then the 6-digit email code from #904. Only the layout changes. Google keeps the existing `/auth/google` flow, as on `login.blade.php`.

**Layout at 1280:** two equal columns.
- **Left, the value side:**
  - **Background:** the paper canvas with a faint #D6D3CB dot grid (18px).
  - **Logo:** `klassapp-horizontal-light.svg`, top-left.
  - **Headline:** Sora 700 44px, "Run your school **where parents already are**", with the second half in #15803D.
  - **One-line lede:** "Attendance, marks, report cards and fees in one place, shared with parents on WhatsApp."
  - **Four ticked points:** each a shipped fact (WhatsApp report cards and fees; guided setup; works for every school from nursery to secondary, with its own classes, streams, terms and currency; start free). **Check each is live before launch**, and drop any that isn't. Per the 2026-10-01 brand rule, no country-only framing.
  - **Footer:** the open-source line, verbatim, at the foot of the column.
- **Right, the form side:**
  - **Background:** white.
  - **Top right:** "Have an account? **Sign in**".
  - **Form:** centred, max-width 420px:
    - the title "Create your school account";
    - **Continue with Google**, 48px, outlined, with the Google G (copy the exact SVG from `login.blade.php`);
    - an "or" divider;
    - an **Email address** field, 48px, with a visible label;
    - a **Password** field, 48px, with a visible label and a 44px show/hide button inside the field. Its hint text is **copied from the existing validation rules**; don't invent new ones;
    - **Create account** (primary, 48px);
    - the line "Next, we'll email you a 6-digit code to confirm your address." with the Terms and Privacy links.
- **Terms and Privacy links:** each gets a 44px hit area (`padding:13.5px 2px; margin:-13.5px 0`) without changing the line height.
- **Step 2, "Check your email":** replaces the form after submit, on the same page.
  - a 48px mail icon box;
  - "We sent a message to **{email}**. Tap **Confirm email** in it, or enter the 6-digit code here.";
  - one **6-digit code** input, 56px, with `inputmode="numeric"`, `autocomplete="one-time-code"` and `maxlength="6"`. It's a single field, not six boxes, so paste and SMS/email autofill work;
  - **Confirm email** (primary, 48px);
  - a status line (`role="status"`): "Confirmed on your phone? This page continues on its own.";
  - **Resend** and **Change email** as 44px text buttons.
  - The expiry and resend throttling follow #904 exactly.

## Part D2 · Verification email with both the code and a link (decided 2026-10-01)
**Concept:** `concepts/profiles/email-verify.html`. It shows the email at 600 and 375, the updated "Check your email" step, the other-device page at 1280 and 375, and the same-device, expired and invalid states.

**The email** uses the Task B email shell: 600px tables, system fonts, the PNG logo with alt text, hex colours only, and a plain-text version.
- **Subject:** "Your KlassApp code is {code}", with the code in the subject so phone notifications show it.
- **Preview text:** "Enter it on the sign-up page, or tap Confirm email."
- **Body:**
  - the heading "Confirm your email";
  - "Enter this code on the KlassApp sign-up page:", then the code in 36px monospace, spaced as "482 915";
  - "Or confirm with one tap:", then the **Confirm email** button: #15803D, white text, 48px tall, full width at 375;
  - the expiry line, taken from #904's real value, never hard-coded in copy.
- **Footer:**
  - "Didn't sign up for KlassApp? You can ignore this email; no account is created until the email is confirmed." **Check that the sentence is true for #904**; if the account exists before confirmation, say "no one can use it" instead;
  - the full link as plain text, for when the button doesn't work.
- **Plain-text version:** the code, the link, the expiry and the ignore line.

**The link:**
- **Route:** `URL::temporarySignedRoute('register.verify.link', $expiresAt, ['token' => $token])`, where `$expiresAt` is the same expiry as the code.
- **Token:** random, stored hashed, and tied to the pending registration.
- **Single use across both methods:** confirming by code or by link uses up **both**.
- **Resend:** a new message gives a new code and a new link and cancels the old ones.
- **Link scanners:** a GET shows a page with a **Confirm email** button that POSTs the token, so mail-system link scanners and previews can't confirm by opening the link. Only the POST confirms.
- **The confirming device is never signed in.** The link only marks the email as confirmed; no session is created on that device.

**Where the link was opened:**
1. **Same browser** (the pending sign-up session cookie is present): after the POST, go straight to the next step, onboarding step 1, with an "Email confirmed" toast. No separate page.
2. **Another device:** the **"Email confirmed"** page.
   - a green check;
   - "**{email}** is confirmed.";
   - "Go back to the device where you signed up. That page continues on its own.";
   - a **Sign in on this device instead** button, going to the normal sign-in;
   - "You can close this page."
   - It **doesn't** show the school name or any account data.
3. **Expired or already used:** "This link has expired", with **Send a new code** (primary; it needs the email, so it goes to the resend form) and **Sign in**, plus the line "Already confirmed? Just sign in."
4. **Invalid** (bad signature or a truncated link): "This link doesn't work", with **Enter the code** (back to the sign-up tab's code step).
   - **Status:** 403 for an invalid link and 410 for an expired one. Use the same plain-language page style as #874's 410 page.

**The original sign-up tab continues on its own:**
- **Checking:** while "Check your email" is visible, it calls `GET /register/verify/status` every 5 seconds, and again on `visibilitychange`. The endpoint returns `{confirmed: bool}` for the current pending session only.
- **When confirmed:** it moves to the next step with the "Email confirmed" toast.
- **When the code expires:** it stops checking and shows the expired state with **Resend**.

**Accept:**
- Confirming by code, by link in the same browser, and by link on another device (a second browser) all lead to the same account state.
- The original tab moves on within 5 seconds of confirming on the other device, measured.
- A GET on the link alone doesn't confirm (tested).
- A reused link shows 410. A tampered signature shows 403.
- Resend cancels the old code and link.
- The email renders in Gmail web, Gmail Android and Outlook desktop, with the button at least 44px tall, and the plain-text version includes the link.
- The confirmed, expired and invalid pages have no sideways scroll at 375 and 1280, and every target is at least 44px.
- **Nothing else:** no Toshi mention, and no screenshots of real schools.
- **Later option (not in this PR): password-free sign-up.** Email, then a 6-digit code, with the password set later or never, alongside Google. Revisit it once #904's code flow has been stable in production. The layout already supports it: remove the password field and rename the button "Continue".

**At 375:**
- The **form comes first**, with the value points below it.
- The headline drops to 28px and the open-source footer is hidden. It stays on the landing page and in the footer of every public page.

**Accept:**
- Google and email-and-password both reach the existing flows, and the code step matches #904's behaviour: expiry, resend and wrong-code message.
- At 375 and 1280: no horizontal scroll, every control at least 44px (48px here), and visible labels.
- axe shows no contrast failures.

## PRs for A–C (after P1–P5)
### B0 · `feat(toshi): single enabled switch`
- **Scope:** `Toshi::enabled()`, `@toshi`, and the view-shared `$toshiEnabled`. Then wrap entry points 1–11.
- **Accept:** with no AI key, a feature test renders the dashboard, a teacher page and a parent page, and asserts no `toshi` (case-insensitive) in the HTML and no `toshi-ui.css` request. With a key and `toshi_enabled`, the panel renders collapsed.

### B1 · `feat(dashboard): v2 layout, setup banner and chip, snapshot, empty states`
- **Scope:** the Part B v2 design, plus the grouped sidebar with "New" badges.
- **Accept:**
  - The step count equals `OnboardingStepsService`'s total and done count for a seeded school with 0, 3 and 7 steps done.
  - Firstname "MUCUNGUZI" renders "Welcome back, Mucunguzi".
  - Dismissing the banner persists across devices, and the chip shows the same count.
  - The chip disappears at 7/7.
  - At most 2 "New" badges are shown at once.
  - The academic-year select exists and is labelled at 375.
  - Toshi doesn't open on a reload with setup incomplete.
  - Screenshots at 1280 and 375 for all six states.

### A1 · `feat(chrome): account card`
- **Scope:** replaces `profile-dropdown` in the sidebar footer.
- **Accept:**
  - A keyboard-only test: Tab to the trigger, Enter, ↓ ↓, Enter follows Settings; Esc returns focus.
  - A click outside closes the card.
  - With a 60-character name and email, both end in "…" and start at the left edge.
  - Rows are 44px (`getBoundingClientRect`).
  - The collapsed rail still works.

### D1 · `feat(auth): sign-up page`
- **Scope:** Part D (layout only; the flow is unchanged).
- **Accept:** as listed in Part D, plus screenshots at 1280 and 375.

### D2 · `feat(auth): confirm email by link as well as code`
- **Scope:** Part D2: email template, signed route, confirm page, other-device, expired and invalid pages, the status endpoint and checking.
- **Order:** after D1, or on its own; it only extends #904.
- **Accept:** as listed in Part D2.

**Every PR:** the regression checks listed above, and a knowledge.md stamp.

## Decisions (2026-09-30, round 2)
- **Setup steps:** the coding agent confirms the real list from `OnboardingStepsService`. The 7 in the concepts are placeholders.
- **Toshi activity link:** the coding agent finds where it's linked from and wraps it with the switch.
- **Onboarding reminder:** delete it if it's unused (see Part C, row 7).

## Open questions
None.
