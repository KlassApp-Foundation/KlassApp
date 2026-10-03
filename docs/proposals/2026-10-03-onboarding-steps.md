# Proposal: Shared onboarding step registry (wizard + Toshi v2)

**Date:** 2026-10-03  
**Status:** proposed — implement behind `toshi.onboarding_v2` (default off)  
**Tagline context:** KlassApp — an open education protocol for humans and agents.

## 1. Goal

Define each onboarding step **once** (contract + registry). Both the manual wizard and Toshi’s chat path consume that registry. Persistence stays on `OnboardingEngine` + completion on `OnboardingStepsService`. The database is the only checkpoint.

## 2. Step contract

One PHP class per step key (namespace e.g. `App\Onboarding\Steps`). Each implements:

| Member | Role |
| --- | --- |
| `key(): string` | Canonical key (`school_name`, `terms`, …) matching `OnboardingStepsService::ALL_STEPS` |
| `applies(School $school): bool` | School type / previous answers (Uganda → EMIS; UNEB → category + centre) |
| `question(): string` | Prompt shown in Toshi (and available to the wizard for help text) |
| `inputType(): string` | `text` \| `choice` \| `list` \| `phone_otp` \| `plan` \| … |
| `options(School $school): array` | Buttons / selects when applicable |
| `normalize(mixed $raw): mixed` | Reuse existing normalisers (`OnboardingNameListExtractor`, phone, student size, curriculum synonyms, `OnboardingEngine::standardNameForClass`, term status normaliser) |
| `validate(School $school, mixed $normalized): void` | Throws / returns errors — same rules as today |
| `save(School $school, mixed $normalized, ?int $userId): void` | Calls existing `OnboardingEngine` methods only |
| `isComplete(School $school, ?int $userId): bool` | Delegates to `OnboardingStepsService::isStepComplete` (including skipped optional steps) |
| `required(): bool` | Inverse of membership in `OPTIONAL_STEPS` (`teachers`, `students`) |

Confirmations (Yes/No) belong to the step that asked; only that step consumes the reply.

## 3. Registry

`StepRegistry::forSchool(School $school): list<OnboardingStep>` returns the ordered applicable list (same order as `OnboardingStepsService::applicableSteps` today).

Toshi conversation state = **next unfinished** registry step from the database. No drafts, no `commitAll`, no separate review-and-commit save on the v2 path. Each accepted answer calls the step’s `save` immediately.

## 4. Unrecognised typed text

If the step cannot normalise/validate the reply:

1. Stay on the same step.
2. Re-ask the same question with its buttons/options.
3. Append: *“Toshi's assistant is coming soon; for now, please choose one of the options above.”*

Never leave the step. After setup is finished: existing Coming soon card (unchanged).

## 5. Toshi-only steps disposition

| Step | Disposition |
| --- | --- |
| **plan** | Registry step; `OnboardingEngine::savePlan` + StepsService completion check |
| **exams** | Leaves onboarding (post-setup) — not in shared registry |
| **SchoolPay** | Leaves onboarding — not in shared registry |
| **co-admin invite** | Leaves onboarding (post-setup invite flows) |
| **school creation (create mode)** | `SchoolSignupBootstrapService` only — not a registry step |

## 6. Pause / resume / Approvable

- No new pause-and-resume mechanism. Reloading reads unfinished steps from the DB (plus `onboarding_finished_at` / `onboarding_skipped_steps` for the wizard).
- `Approvable` stays reserved for assistant / MCP write tools — not onboarding saves.

## 7. Migration path

1. Ship step classes + registry + contract tests (`toshi.onboarding_v2` unused).
2. Toshi v2 path behind `config('toshi.onboarding_v2')` / `TOSHI_ONBOARDING_V2` **default false**. Legacy Toshi draft/`commitAll` path remains.
3. Parity test: same answers via wizard and Toshi v2 → same DB snapshot.
4. Enable v2 for test schools only; run Toshi journeys.
5. Only if green: wizard renders from the registry (look/behaviour preserved). If not, leave the wizard on current code.
6. Remove old Toshi path only after Rasta sign-off (later PR).

## 8. Out of scope here

- Renaming section display names to `P.1` / `S.1` (keep Primary One / Senior One).
- Nightwatch APM repair on staging (separate ops ask).
- Production deploy.
