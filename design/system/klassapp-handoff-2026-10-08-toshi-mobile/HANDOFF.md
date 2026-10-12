# Handoff: Toshi on phones and tablets, 2026-10-08

- **Scope:** how Toshi opens and works below 1280px, for the **admin role first**, as a pattern every role reuses. **Desktop (1280 and up) is unchanged:** the right-hand dock with logo, "Toshi", mode menu, restart, maximise and close.
- **Designed from:** `main @ 254aa194a7ef` (the last SHA read for the admin MVP pack). Before starting, run `git log --oneline 254aa194a7ef..main -- packages/toshi-ui resources/views/layouts resources/views/livewire app/Support`.
- **Concept:** `concept/index.html`, the review page with every screen at 375, 414 and 768. It runs `concept/toshi.html`, a click-through: open, close (✕, Esc, Back, swipe down), mode menu, checklist and conversation views. The Design System tab has a **"08 · Toshi"** group with six cards.
- **Builds on:** the combined admin MVP handoff (`handoff-2026-10-08-admin-mvp-combined.md`) and its **Global rules**:
  - currency from school settings;
  - phone numbers are examples only;
  - Gender: Female / Male / Not specified;
  - no country-specific wording.

  It also builds on the Toshi palette (`[data-toshi-root]` in `tokens/colors.css`), the bot avatar (38px circle, 1px green ring, 24px K), the profiles handoff Part C (entry points, `Toshi::enabled()`) and Lucide icons. **The admin MVP pack itself is not changed.**
- **Superseded:** the profiles handoff Part B line "Dock (375): a floating 44px button at the bottom right". It's now marked superseded there.
- **Not measured in a browser.** Both previews stopped loading pages while this was built, so nothing here has been checked with `getBoundingClientRect` yet. `concept/measure.html` runs the check automatically:
  - all 17 states at 375, 414 and 768;
  - sideways scroll, targets under 44px, content wider than the sheet;
  - any launcher inside the bottom zone, anything covering the sticky form bar, and the composer above the keyboard;
  - one interaction test: open, focus, scroll lock, Esc, focus return, Back.

  **Run it and paste the results into PR1 and PR2.** Contrast is calculated from hex.

## Timing against 17 October
| PR | Piece | Timing |
|---|---|---|
| T1 | Launcher and bottom safe zone | **Optional before 17 October** (the demo is on a laptop). Small, and safe to build after the admin pages: CSS and one Blade partial |
| T2 | Preview sheet (phone bottom sheet and tablet side sheet) | **Optional before 17 October** (the demo is on a laptop). Small, and safe to build after the admin pages; needs T1 |
| T3 | Setup guide screens | Later; needs the setup-guide mode to be live |
| T4 | Assistant screens (early access) | Later; needs the assistant tools and approvals |

T1 and T2 together give every phone and tablet one quiet way into Toshi, in **preview mode**, with nothing promotional. They don't depend on T3 or T4. **Build the admin pages first;** the demo doesn't need T1 or T2.

## Global rules for these PRs
- **Toshi never opens by itself:** not on first login, not after setup, not on errors, not on any page load. Remove any auto-open code you find (the `onboarding-reminder` partial was already marked for deletion in profiles Part C).
- **Toshi off** (`Toshi::enabled()` false): no launcher, no "Set up with Toshi", no sheet markup, no `toshi-ui.css`. The admin MVP's quiet "coming soon" line is for this case only. With Toshi on, the launcher replaces it.
- **The agreed description is used only on the preview card, word for word:**
  > KlassApp is an education protocol that runs in the tools educationists already use. Admins manage school operations in Slack, teachers enter marks from spreadsheets, and parents receive their children's school updates on WhatsApp, all by chatting in natural language with Toshi, your school's AI assistant.
- **"Early access"** appears only in assistant mode: the launcher pill and the sheet header pill.
- **No features that aren't live:** no copy describes them. Example prompts and chips must only name tools that exist.
- **Targets and contrast:** every target is at least 44px tall, and text is at least 4.5:1.
  - **Clay text:** #9A4A2E on #f5f4ed (5.61) or `--toshi-accent-deep` #A84D32 on white (5.57).
  - **Clay buttons:** white on #A84D32 (5.57); hover #9A4A2E.
  - **Never use these:** `--toshi-accent` #c96442 as a text background (3.90), or `--toshi-label-text` #87867f for text.
  - **Secondary text in the sheet:** #5C5646 (6.63 on #f5f4ed, 7.31 on white).
- **Keyboard and screen readers:** full keyboard access and screen-reader labels.
- **Reduced motion:** nothing slides or animates. The typing dots and skeletons are static.

---

## T1 · `feat(toshi): mobile launcher + bottom safe zone`
**Optional before 17 October** (the demo is on a laptop). Small, and safe to build after the admin pages.

**Tokens.** These are already in the design system (`tokens/spacing.css`); add them to the app's token file:

| Token | Value | Meaning |
|---|---|---|
| `--d-sticky-bar-h` | 68px | a sticky action bar or footer: 44px button + 12px + 12px |
| `--d-safe-bottom` | `max(16px, env(safe-area-inset-bottom, 0px))` | the device inset, never less than 16px |
| `--d-bottom-zone` | `var(--d-safe-bottom)`, or `calc(var(--d-sticky-bar-h) + var(--d-safe-bottom))` under `[data-sticky-bar]` | **the bottom safe zone: nothing floating may sit inside it** |
| `--d-sheet-top-gap` | `max(8px, env(safe-area-inset-top, 0px))` | the gap above the phone sheet |
| `--d-toshi-sheet-w` | 400px | the side-sheet width at 761–1279 |

- **`data-sticky-bar` on `<body>`:** the layout sets it whenever the page renders a sticky action bar (`.fbar`: Cancel, Save and add another, Save and send invite) or a sticky footer.
  - **Why an attribute:** use an attribute, not `:has()`, so the zone works in every browser and the token stays at `:root` level.
  - **Page padding:** content gets `padding-bottom: calc(var(--d-bottom-zone) + 16px)`.
- **Viewport:** add `viewport-fit=cover` to the layout's viewport meta so `env(safe-area-inset-*)` resolves.

**Launcher, recommended: a labelled button in the header (option A).**

| Width | Where | Why |
|---|---|---|
| ≤ 760 | in the 56px mobile top bar, between the page title and the account avatar | the top bar is outside the bottom zone, so it can never cover a sticky bar, a footer or table actions |
| 761–1279 | a 44px **"Toshi" row in the sidebar**, directly above the account card (where the setup chip and quiet line sit) | the shell shows the sidebar here and has no top bar |
| ≥ 1280 | none; the desktop dock is unchanged | |

- **Look** (≤760): a 44px pill with a 24px K mark (`alt=""`) and the text "Toshi".

  | Mode | Treatment |
  |---|---|
  | Preview | white, 1px #CBD5E1 border, #0F172A text: quiet, no badge, no motion |
  | Setup guide | #f5f4ed background, 1px #e8e6dc border, #141413 text |
  | Assistant | as setup guide, plus an "Early access" pill (11px/700, #9A4A2E on white, 1px #e8e6dc border) |

- **The title truncates first:** the top bar holds menu, title, Toshi and account. At 375 the title gets about 140px.
- **Semantics:** a `<button>` with `aria-haspopup="dialog"`, `aria-controls="toshi-sheet"`, `aria-expanded`, and `aria-label` "Open Toshi", "Open Toshi, in preview" or "Open Toshi, early access".
- **Remove:**
  - the zero-width edge toggle below 1280 (hide `#toshi-toggle` under `@media (max-width:1279px)`);
  - any remaining floating pill CSS or JS;
  - **the legacy floating-widget rule, before the sheet ships.** It must be deleted or scoped before the sheet ships. It pins **every** element carrying `data-toshi-root`. Moving the attribute to an inner element doesn't avoid it: the concept tried that and the wrapper was pinned instead.
    - **What it does:** pins the element `position:fixed`, 24px from the right and bottom. On the sheet that leaves a 24px gap at the right and bottom, takes the content out of the sheet's flow (so the header scrolls off screen in long conversations), and puts the composer under the keyboard.
    - **Where it lives in the app** (checked at `main @ ca9203730d98`, 2026-10-08):
      - **`public/css/dashboard-refresh.css`, block "Toshi Widget Base" (about L618):** `body [data-toshi-root]{ … position:fixed!important; bottom:24px!important; right:24px!important; z-index:9999!important }`. **This is the rule.**
        - **Keep** its `--toshi-*` colour variables.
        - **Delete** the four position declarations, or move them under a class only the old widget has. Below 1280 the widget they position is replaced by the sheet.
      - **`packages/toshi-ui/resources/css/toshi-ui.css`** (published as `vendor/toshi-ui/toshi-ui.css`, loaded after it):
        - **Don't touch the dock:** the ≥1280 dock block re-declares the same selector with its own `!important` geometry (about L91–115). It's the desktop dock and stays unchanged; after the deletion it's the only positioning that rule has.
        - **Also below 1280:** the same file positions `[data-toshi-root] .toshi-panel` as a fixed drawer (about L224–226) and the collapsed `.toshi-pill` fixed at `bottom:24px` (about L332–338). Remove both for widths below 1280.
      - **Other fixed rules in `dashboard-refresh.css` that go below 1280:**
        - `@media (max-width:640px){ [data-toshi-root] .toshi-panel{position:fixed;inset:0;…;z-index:9998} }`
        - the `body [data-toshi-root] .toshi-toggle{position:fixed!important;…}` edge toggle and its collapsed rules (about L670–700).
      - **The design system's copy:** `styles/classes.css` mirrors the rule. It changes in the same PR.
      - **Before deleting:** run `grep -rn "data-toshi-root" public/css packages/toshi-ui resources`. My search was bounded and may have missed another copy.
    - **Where the attribute goes:** on an inner wrapper (`<section class="sheet" role="dialog"><div data-toshi-root>…</div></section>`), so the sheet's own position never depends on the palette scope.
    - **Accept:** a test that the computed `position` of `[data-toshi-root]` inside the sheet is `static`, and its rect equals the sheet's rect, at 375, 414 and 768. At 1280 the dock is unchanged (screenshot).
  - **The concept's workaround is concept-only:** it overrides the legacy rule with `.sheet [data-toshi-root]{position:static!important;inset:auto!important;z-index:auto!important}`. Don't ship that override; delete the legacy rule instead.

**Alternatives considered** (both drawn in the concept):
- **B · Bottom bar item:**
  - **For:** easy thumb reach.
  - **Against:** KlassApp has no bottom navigation, so this adds a whole nav pattern and takes 56px from every page. Sticky form bars would have to stack above it.
  - **Verdict:** only worth it if a bottom nav arrives for other reasons.
- **C · Edge tab that hides on scroll:**
  - **For:** it's small, and it sits 12px above `--d-bottom-zone`.
  - **Against:** it's an unlabelled icon, it covers the right edge of content (table columns, row menus), and it moves. It's the closest thing to the old pill and the zero-width toggle, both of which failed.

**Accept:**
1. **Screenshots** at 375, 414, 768 and 1280 of the dashboard and the add-teacher form, in preview, setup and assistant modes, before/after.
2. **Measured** (`getBoundingClientRect`):
   - the launcher is 44px tall;
   - no launcher is inside `--d-bottom-zone`;
   - nothing overlaps `.fbar`;
   - no sideways scroll.
3. **Toshi off:** no launcher in the DOM.
4. **Never auto-opens:** a test that loads the dashboard, a teacher page and a form, and asserts the sheet is closed.
5. **Safe-area inset:** on an iPhone with a home indicator, the sticky bar and the zone include the inset.

## T2 · `feat(toshi): mobile sheet + preview mode`
**Optional before 17 October** (the demo is on a laptop). Small, and safe to build after the admin pages. Needs T1.

**Sheet**
- **Size by width:**
  - **≤ 760: bottom sheet.** It reaches from `--d-sheet-top-gap` to the bottom (`100dvh`), with a 16px radius on the top corners, over a scrim of `rgba(15,23,42,.45)`. A drag handle (40 × 5px, #CBD5E1, decorative) sits above the header.
  - **761–1279: side sheet.** `--d-toshi-sheet-w` (400px), full height, from the right, with a 16px radius on the left corners, the same scrim and no handle. The page keeps its width behind it.
  - **≥ 1280:** the dock, unchanged.
- **Header:** 38px bot avatar (circle, 1px #22C55E ring, 24px K), "Toshi" in Sora 600 17px, and a mode pill (Preview / Setup guide / Early access). Then three 44px buttons: **mode menu** (`chevrons-up-down`), **New conversation** (`message-square-plus`, hidden in preview) and **Close** (`x`). There's no maximise on phones.
- **Mode menu:** `role="menu"` with `menuitemradio` items Setup guide and Assistant. Unavailable modes are `aria-disabled` with a reason ("In preview", "Not available yet"). This makes the modes discoverable without promising them.
- **Opening:**
  - **Focus:** it moves to Close.
  - **History:** `history.pushState({toshi:1})`, so the browser **Back button closes the sheet** (`popstate`) instead of leaving the page.
  - **The page behind:** gets `inert` and `aria-hidden`, with `body{overflow:hidden}`.
  - **Scrolling:** the message list has `overscroll-behavior:contain`.
- **Closing:**
  - **Ways to close:** ✕, Esc, Back, a tap on the scrim, or **swipe down** on the handle or header. Over 120px, or a fling faster than 0.5px/ms, closes it; less springs back.
  - **Focus:** returns to the element that opened the sheet.
  - **Draft:** text in the composer is kept for next time.
- **Semantics:** `role="dialog"`, `aria-modal="true"`, `aria-labelledby` pointing at the "Toshi" title. Focus is trapped (Tab and Shift+Tab cycle).
- **Motion:** a 240ms slide (`cubic-bezier(.2,.8,.2,1)`) up on phones and in from the right on tablets. Under `prefers-reduced-motion` the sheet appears with no movement.
- **Keyboard open:** listen to `visualViewport` `resize` and `scroll`, and set the sheet's `bottom` to `innerHeight − visualViewport.height − visualViewport.offsetTop`. The composer then sits directly on the keyboard and only the messages scroll. Add `interactive-widget=resizes-content` to the viewport meta for Chrome on Android.

**Preview mode** (every school, until Toshi ships)
- **Body:** one card (#f5f4ed, 1px #e8e6dc, 16px radius) with:
  - a "Coming soon" pill;
  - "Toshi is in preview" (Sora 600 18px);
  - **the agreed description, word for word**;
  - one line: "Everything in KlassApp works without Toshi. To finish setting up your school, use Continue setup on the dashboard." (with a link).
- **Nothing else:** no chips, no feature list, no screenshots.
- **Composer:** shown but disabled. The textarea is `disabled` with `aria-describedby` pointing at the note "Messaging is paused while Toshi is in preview." (a `pause` icon, #5C5646). The send button is disabled.

**States** (in all modes):

| State | What shows |
|---|---|
| Loading | three skeleton bubbles, `aria-busy`, and a "Loading Toshi…" status; the composer is disabled with "Loading…"; static under reduced motion |
| Offline or error | a card (`role="alert"`): "Toshi can't connect", "You seem to be offline, or Toshi didn't respond. Check your connection and try again." and **Retry** (44px); the composer is disabled with "Messaging resumes when you're back online." |
| Long content | messages wrap with `overflow-wrap:anywhere` and are at most 88% wide; tables in cards never widen the sheet |

**Accept:**
1. **Screenshots** at 375, 414 and 768: closed, opening and open, and preview, loading and offline.
2. **Measured:**
   - no sideways scroll;
   - every target ≥ 44px;
   - nothing wider than the sheet;
   - with the keyboard open, the composer's bottom is at or above the keyboard's top.
3. **Interaction test:** open from the launcher, and focus is on Close. The page behind can't scroll or be focused. Esc closes and focus returns to the launcher. Opening again and pressing Back closes the sheet and stays on the page.
4. **Swipe:** on a real phone, swiping down 120px closes; 60px springs back.
5. **Screen reader** (VoiceOver and TalkBack): it announces "Toshi, dialog", the disabled composer reads its reason, and nothing behind the sheet is reachable.

## T3 · `feat(toshi): setup guide on phones` (later)
- **Views:** a segmented control under the header (`role="tablist"`, 44px tabs): **Checklist** and **Conversation**. They show the same session.
- **Checklist:**
  - **Progress:** "4 of 7 steps done", plus a bar (`role="progressbar"`) and "The same steps as Continue setup on the dashboard. Progress is shared."
  - **Steps:** each is a 56px button with a status circle (done: #15803D with a check; current: a #A84D32 ring; to do: #CBD5E1), the step name and one line ("Done · …", "Next · …").
  - **Opening a step:** tapping one opens it in Conversation.
  - **One source of steps:** they come from `OnboardingStepsService`, the same list the wizard and setup bar use.
- **Conversation** (`aria-live="polite"` on the list):
  - **Bot messages:** #f5f4ed, labelled "Toshi". The user's are rgba(201,100,66,.10).
  - **Confirm chips:** **Yes** and **No, …**, 44px.
  - **Plan cards:** a radio `fieldset` with a legend; each option is a 44px+ label, and the selected one gets a #A84D32 border. Then **Use this plan**.
  - **Review card:** "Check before I save" with the values in the school's currency. **Change amounts** and **Save fees**. **Nothing is saved until the user confirms.**
  - **Name-list upload card:** .xlsx or .csv with first name, last name and class; gender and date of birth optional. **Choose file** and **Download a template**. "Students aren't added until you confirm the review."
  - **Quick replies:** a sideways-scrolling row of 44px chips above the composer, for example "Skip this step", "I'll add students one by one" and "Show all steps". It scrolls inside its own row, never the page.
- **Empty conversation:** "I can walk you through the {n} setup steps that are left, one question at a time. You can stop at any point and pick up later.", with chips for the next step and "See all steps".
- **Entry points:**

  | Entry point | When it shows | What it opens |
  |---|---|---|
  | Top-bar launcher, or the sidebar row at 761–1279 | Toshi enabled | the sheet in the school's mode, at the last view |
  | Dashboard setup card **Set up with Toshi** | setup-guide mode only, setup incomplete; **never in preview** | the sheet on **Checklist** |
  | Sidebar setup chip | after the setup bar is hidden, setup incomplete | **the manual setup flow (Continue setup), never the sheet**, so setup always works without Toshi; it shows the same shared progress |

- **Accept:**
  - screenshots of the checklist, the conversation with each card, and the empty conversation at 375 and 414;
  - the checklist count matches the setup bar;
  - nothing is written to the database before the review is confirmed.

## T4 · `feat(toshi): assistant on phones (early access)` (later)
- **Header:** the mode pill reads **Early access**, and so does the launcher pill. This is the only mode that uses those words.
- **Free chat:** the composer is enabled, 44px min and 120px max height, with a 44px send button (#A84D32).
- **Typing indicator:** three dots in a bot bubble, `role="status"`, `aria-label="Toshi is typing"`. Static under reduced motion.
- **Tool results:** a card with a title, a source line ("From Fees, just now · 3 of 32 students"), a compact table, and a link to open the full page.
- **Approval card:** required for **anything that changes records or sends messages**.
  - **Title:** "Approve before Toshi does this".
  - **Note:** "This sends messages. Nothing happens until you approve."
  - **List:** exactly what will change: the recipients by name, the message text with its placeholders, and the records written.
  - **Buttons:** **Reject** and **Approve and send**.
  - **Server rule:** a record-changing tool call never runs without an approval row tied to the user and the exact payload.
- **Empty conversation:** "Ask about your school" and one line: "Toshi is in early access. It can look things up and prepare changes; anything that changes records waits for your approval." Then up to 3 example chips. **They must only name tools that are live for that school.**
- **Accept:**
  - screenshots of conversation, empty, long messages and keyboard open at 375 and 414;
  - a test that a record-changing tool call without approval is rejected server-side;
  - "Early access" appears nowhere in preview or setup mode.

---

## What the current phone experience gets wrong
1. **No way in on a phone:** below 1280px the edge toggle is a zero-width sliver, so on a phone the only way to open Toshi is the setup card's "Set up with Toshi". Once that card is hidden or setup is done, Toshi can't be reached.
2. **No safe zone:** the floating "Toshi Agent · Talk" pill covered footer actions on teacher pages, which is why it was removed. Nothing reserves space for sticky action bars or footers, and there's no bottom safe-zone token.
3. **No tablet layout:** below 1280 the panel falls back to the old floating widget from the main stylesheet, and goes full-screen only at 640px and below. Nothing is defined between 641 and 1279.
4. **No phone behaviour:** no swipe to close, the Back button leaves the page instead of closing Toshi, the page behind still scrolls, and focus isn't trapped.
5. **Promotional while in preview:** the dashboard promotes Toshi with a carousel and an open side panel with a disabled message box, instead of one quiet entry point.

## Decisions (2026-10-08)
1. **Launcher at 761–1279:** a **sidebar row**, above the account card. No new top bar on tablets.
2. **Tablet side sheet:** starts at **761px**, the shell's own drawer breakpoint.
3. **Conversation history:**
   - **Assistant:** a conversation is kept for the **sign-in session**. Closing and reopening the sheet shows it again. **New conversation** in the sheet header starts fresh.
   - **Setup guide:** progress lives in the school's data (`OnboardingStepsService`), so nothing is lost when a session ends.
   - **Retention:** how long assistant history is kept beyond the session is **decided later, with the privacy policy**.
   - **Copy:** nothing makes any promise about retention, including empty states, offline and errors, and the New conversation action. The setup guide's "pick up later" refers only to setup progress, which is stored with the school.
