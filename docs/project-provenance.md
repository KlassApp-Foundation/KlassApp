# KlassApp Project Provenance

> KlassApp is a fork of **GeGoK12**, an open-source school management system originally built by **GoGo Technologies** in India.

## What this means

KlassApp inherited a substantial codebase from GeGoK12 — controllers, models, migrations, views, validation logic, and domain assumptions shaped by the Indian education system (CBSE/ICSE). The Ugandan-specific rewrite has replaced most user-facing flows and data models, but **fork-legacy artifacts** persist throughout the codebase. When you encounter code that seems odd, misnamed, or mismatched to the Ugandan/UNEB domain, check this list before assuming it's a bug unique to this project — it may be inherited GeGoK12 code that hasn't been adapted yet.

## Known fork-legacy artifacts

### Indian grade-level references (`'10'`, `'11'`, `'12'`)

The original GeGoK12 validation logic gated `board_registration_number` to Indian class names `'10'`, `'11'`, `'12'` (CBSE 10th board, 11th/12th intermediate). These strings never match Ugandan class names (`P.7`, `S.4`, `S.6`). The old PHP condition `$standard->name == '10' || '11' || '12'` was additionally always truthy due to operator precedence (see AGENTS.md standing rule #14, Known Bug Pattern #7/8).

**Status**: Fixed in PR #375 via `OnboardingEngine::isCandidateClass()`, which matches UNEB candidate classes (P.7/PLE, S.4/UCE, S.6/UACE).

**Remaining instances**: The compiled `public/js/app.js` still contains the old `*Only For Class X , XI , XII` label text from the GeGoK12 Vue components. This is a build artifact — the source Vue files should be checked for any remaining Indian grade references and updated.

### "ID Card Number" → `school_student_id`

The original GeGoK12 column was named `id_card_number` — an Indian-school-issued ID card number. KlassApp renamed this to `school_student_id` via migration (PR #373), reflecting the Ugandan use case: a school-assigned student identifier (admission number, roll number, etc.), not a government ID card.

**Migration**: `2026_08_27_020855_rename_id_card_number_to_school_student_id_on_student_academics_table.php`

**Remaining instances**: Some Vue components and blade templates may still reference `id_card_number` in labels, placeholders, or form models. Search for `id_card_number` in source files (excluding `public/js/` build artifacts and the migration itself).

### Indian demographic fields (`aadhar_number`, `caste`, `sub_caste`, `community`, `mother_tongue`, `lin`)

The `AdmissionUser` trait (`app/Traits/AdmissionUser.php`) and the admission form models carry Indian-specific demographic fields:

- `aadhar_number` — India's national ID (Aadhaar). Uganda has no equivalent national student ID.
- `caste` / `sub_caste` — Indian caste categories. Not applicable in Ugandan school context.
- `community` — mapped to `caste` in the trait. Indian demographic grouping.
- `mother_tongue` — Indian linguistic demographic. Uganda's context would be local language, but this isn't collected in KlassApp's onboarding.
- `lin` — unclear origin, possibly "Local Identification Number" (Indian).

**Status**: These fields exist in the `userprofiles` table and the `AdmissionUser` trait but are not surfaced in KlassApp's current onboarding wizard or Toshi flows. They persist as dead columns/parameters from GeGoK12.

### `board_registration_number` as "Board Registration Number"

The field name itself is Indian-English: "Board" refers to CBSE/ICSE examination boards. In the Ugandan context, this is the **UNEB registration number** (Uganda National Examinations Board). The column name hasn't been renamed, but PR #375 added `is_candidate_class` gating so it's only collected/shown for P.7, S.4, and S.6 classes.

### Admission flow patterns (`AdmissionUser` trait)

The `AdmissionUser` trait's `CreateStudent()` method follows the GeGoK12 admission pattern: creating a `User`, `Userprofile`, and `StudentAcademic` in a single DB transaction with hardcoded demo password, `registration_number` generated from `date('YmdHis')`, and direct DB column writes without Eloquent mass-assignment protection. KlassApp's `OnboardingEngine` and `ManualOnboardingWizard` have replaced this flow for new onboarding, but `AdmissionUser` is still used by the legacy admin admission controllers.

### Sibling details structure (`sibling_relation`, `sibling_name`, `sibling_date_of_birth`, `sibling_standard`)

Stored as a JSON array in `student_academics.sibling_details` — the GeGoK12 pattern for capturing sibling information during admission. KlassApp's onboarding doesn't collect this, but the column persists.

## How to identify fork-legacy code

When you encounter code that seems wrong for the Ugandan/UNEB context:

1. **Check this document first.** If the pattern is listed here, it's a known GeGoK12 artifact.
2. **Check the git blame.** Code that predates the KlassApp fork or was written in the initial bulk import likely came from GeGoK12.
3. **Check for Indian-specific references**: `aadhar`, `caste`, `class X/XI/XII`, `board` (meaning CBSE/ICSE), numeric grade levels (10/11/12 instead of P.7/S.4/S.6).
4. **Assess whether it's dead code or active.** Some GeGoK12 fields are still in the schema but no longer surfaced in any KlassApp UI or API. Others (like `board_registration_number`) have been adapted with Ugandan logic.

## Decision framework for fork-legacy artifacts

- **Actively used but misnamed or misconfigured** → Fix the name/logic to match the Ugandan context (e.g., `board_registration_number` gating via `isCandidateClass()`).
- **Dead columns/fields not surfaced in any KlassApp flow** → Leave in place for now; don't remove columns without a migration and data audit. Document as fork-legacy here.
- **Compiled build artifacts referencing old names** → Rebuild after updating source Vue/Blade files. Don't edit `public/js/app.js` directly.
- **Validation rules referencing Indian grade levels** → Replace with UNEB-candidate-class logic via `OnboardingEngine::isCandidateClass()`.

## Cross-references

- **AGENTS.md standing rule #14** — PHP `||` with string literals is always truthy; locale/domain assumptions from one educational system must not be hardcoded for another.
- **AGENTS.md Known Bug Pattern #7** — `board_registration_number` operator-precedence + wrong-locale bug.
- **knowledge.md Known Bug Pattern #8** — Full root-cause detail for the `board_registration_number` bug.
- **PR #375** — Fixed `board_registration_number` validation and gating.
- **PR #373** — Renamed `id_card_number` → `school_student_id`.
