# Onboarding Defaults Skill: Configurable, But Never Blank

> **Status:** principle documented, not yet implemented in any UI. This skill governs future UI work.
> **Source:** AGENTS.md standing rule #17; `docs/onboarding-engine-plan.md` §7.
> **Scope:** both Toshi chat flows and manual wizard forms — any onboarding step UI.

## Core Principle

**When a data model is configurable, the UI should prefill real-world defaults — not present an empty form.**

The backend (`OnboardingEngine`, `SchoolCategorySeeder`) stores everything as fully configurable: any number of terms, any class names, any fee structures. That flexibility is the right persistence model. But the *user-facing UI* — both Toshi's chat flow and the manual wizard's forms — should start with sensible, real-world defaults pre-filled, which the school admin can then edit, add to, or remove from.

This principle applies to **both interfaces** consistently. A default that Toshi pre-fills should also appear pre-filled in the manual wizard's equivalent step, and vice versa.

## Why

Blank configuration forms are a silent onboarding killer. A school admin faced with "add your terms" and an empty list has to recall and type every term from scratch. The same admin shown "Term 1, Term 2, Term 3" with typical date ranges for their country can review and adjust in seconds. Prefilling defaults reduces cognitive load, prevents errors, and makes the onboarding feel opinionated-in-a-good-way rather than open-ended.

## Known Defaults

These are the defaults that should be pre-filled. Each is drawn from `SchoolCategorySeeder` and the East African / UNEB school context this platform serves.

### Academic Terms

**Default:** 3 terms — "Term 1", "Term 2", "Term 3" — with typical start/end dates for the current academic year.

Every UNEB school in Uganda runs on a 3-term system. There is no common case for 1, 2, or 4+ terms. The UI should present these three pre-filled and let the admin adjust dates or rename them — never start with an empty term list.

Backend source: `OnboardingEngine::saveTerms()` accepts `name`, `start`, `end` per term. The prefill logic should call it with the 3 UNEB terms.

### Classes / Sections

**Default:** depends on `school_category` (already seeded by `SchoolCategorySeeder`).

| Category | Default classes |
|---|---|
| `primary_nursery` | Baby Class, Middle Class, Top Class, P.1–P.7 |
| `primary` | P.1–P.7 |
| `o_level` | S.1–S.4 |
| `a_level` | S.5–S.6 |
| `o_a_level` | S.1–S.6 |

The seeder already provides these. The UI should display the seeded classes pre-filled, not an empty "add a class" form.

Backend source: `OnboardingEngine::saveStandards()`, `SchoolCategorySeeder::seed()`.

### Subjects

**Default:** core subjects per grading tier, per `SchoolCategorySeeder`.

- **Primary:** English, Mathematics, Science, Social Studies, Religious Education, Art & Physical Education
- **O-Level:** English, Mathematics, Physics, Chemistry, Biology, History, Geography, Commerce, Christian Religious Education
- **A-Level:** varies by subject combination (no universal default beyond core English + General Paper)

The seeder already provides these. The UI should show them pre-filled.

Backend source: `OnboardingEngine::saveSubjects()`, `SchoolCategorySeeder::seed()`.

### Fee Categories

**Default:** common fee categories for Ugandan schools — "Tuition", "Boarding", "Lunch", "Development" — with amounts left blank (no default amounts, since those vary per school).

Amounts intentionally left blank: every school sets different amounts, but the *categories* are predictable. Prefilling category names saves typing; leaving amounts blank avoids implying specific numbers.

Backend source: `OnboardingEngine::saveFees()`.

### Academic Year

**Default:** "2025 Academic Year" (or current year), with start = Jan 1 and end = Dec 31 of that year.

The admin should see a pre-filled year name and date range they can adjust, not a blank date picker.

Backend source: `OnboardingEngine::saveAcademicYear()`.

## Implementation Checklist

Before shipping any onboarding step UI (Toshi flow or wizard form), ask:

1. **Does this field have a well-known real-world default for Ugandan / East African schools?** If yes, prefill it.
2. **Is the default already provided by `SchoolCategorySeeder`?** If yes, display what the seeder created — don't re-invent the list.
3. **Is the prefilled value still editable?** It must be. Defaults reduce cognitive load, not agency. The admin can change, add, or remove any prefilled value.
4. **Does Toshi's chat flow prefill the same defaults as the manual wizard?** Both interfaces must present the same starting defaults for the same step.
5. **Are amounts intentionally left blank while category names are prefilled?** For fees, this is the correct pattern. Names are predictable; amounts are not.

## Cross-References

- **AGENTS.md** rule #17 — the standing rule version of this principle
- **`docs/onboarding-engine-plan.md`** §7 — original design doc section
- **`app/Services/OnboardingEngine.php`** — backend persistence methods
- **`app/Console/Commands/SchoolCategorySeeder.php`** — canonical defaults by school category
- **`resources/views/components/DESIGN_SYSTEM.md`** — visual component patterns (form groups, cards) to use when building the UI
