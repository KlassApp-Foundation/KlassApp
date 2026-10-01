# UI kit — Manual onboarding wizard

The flow a school walks through when it sets itself up **without** Toshi.

- `index.html` — the wizard, click-through.
- `WizardApp.jsx` — shell, the real 17-step sequence, dot progress, nav, review.
- `WizardSteps.jsx` — the individual steps.

## Provenance

**Rebuilt against real source** (m0088): `ManualOnboardingWizard.php` and
`manual-wizard-step-fields.blade.php`. The earlier version of this kit was
reconstructed from `manual-wizard-*` CSS alone and was substantially wrong — see
"What changed" below.

### Verified real — from the Livewire component and Blade partial

| Thing | Evidence |
|---|---|
| **17 steps**, in this order: `school_name`, `student_size`, `country`, `curriculum`, `school_category`, `emis`, `uneb_center`, `academic_year`, `standards`, `subjects`, `teachers`, `students`, `terms`, `fees`, `whatsapp_verify`, `plan_selection`, `review` | the Blade `@if/@elseif` chain |
| **One field per step** for the school profile — school name, size, country, curriculum, category, EMIS and UNEB centre are each their own screen | same |
| `standards` **is** the Structure & Class Teacher checkpoint | `@elseif($stepKey === 'standards')` |
| Classes arrive **pre-seeded from school category**; streams and CT invites are both optional | the step's own intro copy, verbatim in the kit |
| Per-class card shows name, stream chips or "No streams yet — undivided base class", and CT name/email or "No class teacher yet" | Blade markup |
| **Add stream**: text input, placeholder `e.g. A, East, Science`, outline "Add" button → `addStructureStream(sectionId)` | Blade + component |
| Stream validation message: "Enter a stream name (e.g. A, East, Science)." | component, verbatim |
| Stream success flash: "Added stream “X” to Y." in green | component + `$structureFlash` |
| **Invite Class Teacher** (only when the class has none): email input, a select defaulting to "— Create a new teacher —" listing existing teachers as `name (email)`, then name + "Phone (optional)" inputs **shown only when no existing teacher is picked**, then "Send invite" → `inviteStructureClassTeacher(sectionId)` | Blade conditional |
| Existing teachers are `usergroup_id = 5`, `status = active`, ordered by name | component query |
| `teachers` and `students` are **OPTIONAL_STEPS** with a **"Skip for now"** ghost button, which confirms first if drafts exist | Blade + `skipOptionalStep()` |
| Bulk steps have **three** input modes: "Download template" link → "Upload file" label, a paste textarea ("Paste names (one per line)", placeholder `John Ssali / Grace Nakamya`, "Add from paste"), then single-add with **name + email + phone** | Blade |
| Bulk row meta is `email · phone`; remove button is `✕` with `aria-label="Remove"` | Blade |
| Teacher name help: "Full name only — put the phone number in the Phone field below." | Blade, verbatim |
| Optional-step footer: "Optional — skip if you’ll add teachers later. Continue saves everyone in the list." | Blade, verbatim |
| `subjects` shows a green "Subjects already set up" panel with chips when seeded, else "First subject" required | Blade |
| `school_category` renders as a `manual-wizard-plan-grid` radiogroup of `manual-wizard-plan-card`s | Blade |
| Plans come from the **`Plan` model** (`is_active = 1`, ordered) — the count is not fixed at 3; the CSS just lays out 3 columns | `getPlansProperty()` |
| Review rows each have an **Edit** link → `editSection(key)`, which jumps to that step and makes Next return to review | component |
| Nav is **Previous / dots / Continue**, with "Confirm & finish" on review | component + `.manual-wizard-nav` |
| Review is a **synthetic step**, not part of the checklist service | component docblock |
| Completion suggestions are **conditional on what was completed** (e.g. "Manage classes & streams" only if `standards` was done) | `getSuggestionsProperty()` |

### Real behaviour modelled but simplified

- **Checkpoints.** The component forces a landing on `standards` once after
  `academic_year`, and on `subjects` once after `standards`, *even when those steps
  are already complete* — because their actions are optional. The kit walks steps
  linearly, which produces the same visible path on a first run.
- **Completion-driven navigation.** Really, `next()` jumps to the next *incomplete*
  step via `OnboardingStepsService`, and mount lands on the first incomplete step.
  The kit is linear.
- Stream and CT actions persist immediately in the real app; here they mutate
  local state.

### Still inferred — flagged, not verified

- **`OnboardingStepsService::STUDENT_SIZE_OPTIONS`** — not supplied. The ranges
  (1-100 … 1000+) are placeholders.
- **`SchoolCategorySeeder::CATEGORIES`** — not supplied. The five categories shown
  are plausible for Ugandan schools, not confirmed.
- **Plan names and prices.** Real plans are DB rows; "Freemium" is confirmed by
  name in the component (`Do NOT auto-assign Freemium on every Next`), the others
  are placeholders.
- **Step titles.** The Blade partial supplies field labels, not screen headings;
  the `h1` text per step is written to the brand's voice.
- **`terms`, `fees`, `whatsapp_verify`** field sets are abbreviated — their Blade
  blocks were read but the kit shows the primary fields only.
- **`OnboardingStepsService`** itself was not supplied, so the canonical step order
  is taken from the Blade chain plus the component's checkpoint logic rather than
  from the service that actually defines it.

## What changed in this rebuild

The CSS-only reconstruction had **6 steps**; the real wizard has **17**. Specifically:

1. **Added the `standards` structure checkpoint** — per-class cards, stream adding,
   and the full class-teacher invite flow. Previously absent entirely.
2. **Split the school profile into 7 single-field steps.** The old kit put four
   fields on one "Tell us about the school" screen; the real wizard asks one thing
   at a time.
3. **Added `subjects`, `terms`, `fees`, `whatsapp_verify`** — all missing before.
4. **Bulk steps gained the paste mode and email/phone fields**, plus the
   "Download template" link and the "Skip for now" button with its confirm.
   Previously upload + name-only single-add.
5. **Review rows now use real step keys** and `editSection` semantics, with the
   "Next returns you to review" state surfaced in the subhead.
6. **Plans are data-driven** rather than a hardcoded three.
7. Corrected the old kit's misuse of `.manual-wizard-bulk-*` on the classes step —
   those classes belong to teachers/students, which is where they now live.
