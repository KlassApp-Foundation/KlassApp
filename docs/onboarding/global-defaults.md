# Global defaults: the Uganda assumptions in onboarding

**Tracker:** T-122. **Status:** catalogue only — no code changed. Rasta approves the table before any change.

**Base:** `origin/integration/monday-2026-11-09`. There are **48** `FLAGGED(product-decision)` markers in `app/` on this base (the audit counted 56 on `main`; the integration branch has already resolved or moved some). Each row is one marker, with the assumption and the default it should take from the school's own settings.

**Rule (D4):** Uganda values may be *defaults at signup*, never schema, validation, completion rules or formatting.

## How to read the proposed default

Every proposal reads from data the school already has: `schools.registration_country` (set by the wizard before any content step), `schools.currency`, `schools.curriculum`, and the country/curriculum profile services (`CountryProfiles`, `SchoolTypePreset`, `PhoneCountry`). None proposes a new column.

## Currency and money — High

**Proposed default:** `schools.currency` from the school's country profile; format with that currency. Never print a currency literal.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/ManualOnboardingWizard.php:1834` | the review formats fee amounts as UGX | wrong amounts shown to parents |
| `app/Livewire/AgentToshi.php:2459` | onboarding fee copy hard-codes UGX | wrong amounts shown to parents |
| `app/Onboarding/Country/UgandaOnboardingProfile.php:9` | EMIS, UGX, uneb, term dates, and class presets are PHP constants | wrong amounts shown to parents |

## Account creation and first sign-in — High

**Proposed default:** Issue a one-time account invite (`StudentParentAccountInviteService` / `TeacherAccountInviteService` on this branch); never store a shared or default password.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/AgentToshi.php:4721` | offers a default password and collects one | access and data protection |
| `app/Livewire/AgentToshi.php:6931` | create mode stores a chosen or random password instead of an invite link | access and data protection |
| `app/Support/UserProvisioning.php:30` | runtime user creation stores a random password | access and data protection |
| `app/Http/Controllers/Auth/RegisterController.php:64` | public signup stores a password instead of sending an invite link | access and data protection |
| `app/Services/Toshi/LiveAdversarialRunner.php:316` | this service creates users with the password 'password' | access and data protection |
| `app/Services/ToshiActionService.php:1391` | adding a parent stores a random password | access and data protection |
| `app/Services/ParentLinkService.php:171` | a linked parent is created with a random password | access and data protection |
| `app/Services/SchoolSignupBootstrapService.php:220` | signup stores the typed password, or a random one, instead of an invite link | access and data protection |

## Phone numbers — High

**Proposed default:** `PhoneCountry::spec($school->registration_country)`: complete only a trunk-prefixed local number for that country; never invent `0700000000`; leave unparseable values empty and report them.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/AgentToshi.php:6900` | missing phone becomes the Uganda local number 0700000000 | parents cannot be reached or matched |
| `app/Helpers/WhatsAppPhoneHelper.php:278` | accepts only +256 numbers matching 07xx mobile prefixes | parents cannot be reached or matched |
| `app/Services/SchoolSignupBootstrapService.php:138` | strips a leading 0 and prefixes +256 for 07xx numbers | parents cannot be reached or matched |

## Email addresses — High

**Proposed default:** Use a real address from the school; when there is none, leave it empty and use the invite hand-over channel. Do not fabricate a domain.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/AgentToshi.php:6898` | fallback school email uses the .sch.ug domain | accounts that can never sign in |
| `app/Livewire/AgentToshi.php:6936` | fallback admin email uses the .sch.ug domain | accounts that can never sign in |
| `app/Services/ToshiActionService.php:1389` | generated parent email uses the .ug domain | accounts that can never sign in |
| `app/Services/ParentLinkService.php:169` | generated parent email uses the .sch.ug domain | accounts that can never sign in |

## Curriculum and examinations — High

**Proposed default:** `schools.curriculum` plus the country profile: grading scales, centre-number field and exam names come from the curriculum, not from UNEB constants.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/ManualOnboardingWizard.php:95` | the wizard opens on uneb | wrong grading for a whole cohort |
| `app/Livewire/ManualOnboardingWizard.php:1966` | a blank curriculum is shown as uneb | wrong grading for a whole cohort |
| `app/Livewire/AgentToshi.php:146` | unanswered picker commits uneb | wrong grading for a whole cohort |
| `app/Livewire/AgentToshi.php:642` | restored draft curriculum falls back to uneb | wrong grading for a whole cohort |
| `app/Livewire/AgentToshi.php:4910` | maps Uganda and NCDC onto the uneb constant | wrong grading for a whole cohort |
| `app/Livewire/AgentToshi.php:6904` | create mode stores uneb when no curriculum was chosen | wrong grading for a whole cohort |
| `app/Helpers/GradingHelper.php:51` | onboarding seeds the Uganda grading scales, including O-level and A-level bands | wrong grading for a whole cohort |
| `app/Services/OnboardingEngine.php:167` | curriculum is a fixed list headed by uneb, not the country profile | wrong grading for a whole cohort |
| `app/Services/OnboardingEngine.php:236` | the centre number stored here is the UNEB centre number | wrong grading for a whole cohort |
| `app/Onboarding/Steps/Steps/UnebCenterStep.php:17` | the exam-centre step is the UNEB centre number | wrong grading for a whole cohort |
| `app/Onboarding/Steps/Steps/CurriculumStep.php:10` | the step offers UNEB, Cambridge, Montessori, and Other only | wrong grading for a whole cohort |
| `app/Onboarding/Steps/Steps/TermsStep.php:108` | typing uneb accepts the default term calendar | wrong grading for a whole cohort |
| `app/Onboarding/Country/GenericOnboardingProfile.php:39` | every country without a profile is offered uneb | wrong grading for a whole cohort |

## Country defaults — Medium

**Proposed default:** `schools.registration_country` (already required by the wizard); default only at signup, never in rules, validation or completion checks.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Livewire/ManualOnboardingWizard.php:100` | the wizard opens on Uganda | wrong labels and skipped steps |
| `app/Livewire/ManualOnboardingWizard.php:113` | the class field opens on the Uganda short name P1 | wrong labels and skipped steps |
| `app/Livewire/AgentToshi.php:7291` | class and subject lists are Uganda nursery, P1–P7, and S1–S6 constants | wrong labels and skipped steps |
| `app/Services/OnboardingStepsService.php:199` | step rules still special-case the country name Uganda | wrong labels and skipped steps |
| `app/Services/OnboardingEngine.php:351` | candidate classes are the Uganda P.7 / S.4 / S.6 constants | wrong labels and skipped steps |
| `app/Services/OnboardingEngine.php:434` | ordinals cover Uganda Primary 1–7 and Senior 1–6 only | wrong labels and skipped steps |
| `app/Onboarding/Steps/StepSchemaCatalog.php:93` | the country field defaults to Uganda | wrong labels and skipped steps |
| `app/Onboarding/Steps/StepSchemaCatalog.php:97` | the country answer defaults to Uganda | wrong labels and skipped steps |
| `app/Onboarding/Steps/StepSchemaCatalog.php:506` | an empty country table falls back to Uganda, Kenya, and Tanzania | wrong labels and skipped steps |
| `app/Onboarding/Steps/Steps/EmisStep.php:40` | the skip copy names Uganda rather than the country profile | wrong labels and skipped steps |
| `app/Onboarding/Country/GenericOnboardingProfile.php:47` | other countries receive Uganda's term dates | wrong labels and skipped steps |
| `app/Onboarding/Country/SchoolTypePreset.php:27` | A-level streams are the Uganda combinations PCM, BCM, HEG, and MEG | wrong labels and skipped steps |
| `app/Onboarding/Country/SchoolTypePreset.php:59` | the only preset is Uganda's nursery, P1–P7, O-level, and A-level lists | wrong labels and skipped steps |

## Structure, classes and calendar — Medium

**Proposed default:** `SchoolTypePreset` / country profile: class names, ordinals, term calendar and school types per country and curriculum.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Services/SchoolCategorySeeder.php:29` | school types are Nursery, Primary, O-Level, and A-Level constants | wrong classes and terms |
| `app/Services/SchoolCategorySeeder.php:63` | seeded classes are Baby/Middle/Top, Primary One–Seven, and Senior One–Six | wrong classes and terms |
| `app/Services/OnboardingEngine.php:397` | maps class names onto nursery, O-level, and A-level constants | wrong classes and terms |

## Other — Low

**Proposed default:** Review individually; each row states the assumption in its comment.

| File:line | Assumption in the comment | Who it affects |
| --- | --- | --- |
| `app/Helpers/WhatsAppPhoneHelper.php:227` | display grouping is special-cased for a 12-digit 256 number | copy or local detail |

## Why this lists 48, when the audit counted 56

The audit read `main` at `01d87832` and counted 56 markers. This catalogue reads `origin/integration/monday-2026-11-09`, where eight of those markers no longer exist because the code they flagged was changed by work already merged into #1285. They are **already fixed on the base**, not out of scope:

| # | Marker on `main` | File | What changed on #1285 |
| --- | --- | --- | --- |
| 1 | "a leading 0 or a short number is always rewritten to +256" | `app/Helpers/WhatsAppPhoneHelper.php` | The helper now parses with `libphonenumber` (`PhoneNumberUtil`) instead of prefixing +256 |
| 2 | "create mode stores Uganda when no country was chosen" | `app/Livewire/AgentToshi.php` | The v1 create payload no longer writes `registration_country`; the marker line is gone |
| 3 | "a blank country is shown as Uganda" | `app/Livewire/ManualOnboardingWizard.php` | The wizard reads `OnboardingStepsService::countryOrDefault()` and renders a blank as "—" |
| 4 | "a new parent gets a random password, not an invite link" | `app/Services/OnboardingEngine.php` | Parent creation goes through `StudentParentAccountInviteService` |
| 5 | "a new student gets a random password, not an invite link" | `app/Services/OnboardingEngine.php` | Student creation sets no password (`is_reset = 1`) and issues a setup invite |
| 6 | "a new teacher gets a random password, not an invite link" | `app/Services/OnboardingEngine.php` | Teacher creation goes through `TeacherAccountInviteService` |
| 7 | "adding a teacher stores a random password" | `app/Services/ToshiActionService.php` | Add-teacher uses `TeacherAccountInviteService::emailIsUsable()` and an invite |
| 8 | "known countries come from a fixed list, not libphonenumber" | `app/Support/PhoneCountry.php` | `PhoneCountry` now delegates to `libphonenumber` |

So: **56 on `main` = 48 still present on the integration base + 8 resolved by merged work**. None of the eight was dropped as out of scope. `adding a parent stores a random password` (`ToshiActionService.php:1391`) is a different marker and is still present — it is row 8 of the account-creation group above.

## Order of work

1. **Currency and money** — a wrong amount is visible to a parent and is the only group that changes a number rather than a label.
2. **Account creation and first sign-in** — align the remaining random-password paths with the invite service (T-094).
3. **Phone numbers** and **email addresses** — the two ways a person is reached; both need a "no value" state instead of a fabricated one.
4. **Curriculum and examinations** — grading scales and centre numbers per curriculum.
5. **Country defaults**, **structure and calendar** — labels, class names, term dates.
6. **SchoolPay** — remove from onboarding (D3), keep as a settings feature.

## What is not proposed

- No new country, currency or curriculum table in this pass. The profiles already exist; the work is to make the call sites read them.
- No change to what a school can configure. This is about defaults and validation, not about removing choice.
