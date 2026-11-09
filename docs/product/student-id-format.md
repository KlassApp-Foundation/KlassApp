# Student ID format (KlassApp number)

Design note only. No code changes. Serves **T-072**. References are to `main` at `01d87832`.

**Why a note and not code.** Every file that generates or checks the ID is on the #1285 integration branch's changed-file list (`StudentIdGeneratorService`, `User`, `StudentAcademic`, `RegisterUser`, `WhatsAppController`, `SeedStudentIdSequences`, `UserprofileForm`, `MarksImportService`, `ParentLinkRequestService` and the two ID tests), so a code change here would conflict with that branch. This note is what the change should be, so it can be built after #1285 lands.

## What exists today

- **Format:** `KLS` + 3-digit school id + 4-digit sequence, for example `KLS0010001`. Always letters `KLS` followed by exactly 7 digits. No hyphens or spaces are generated.
- **Generator:** `StudentIdGeneratorService::next()` (`app/Services/StudentIdGeneratorService.php`) reserves the next number per school from `student_id_sequences` (`school_id` primary key, `next_seq`) under a row lock, and returns `sprintf('KLS%03d%04d', $schoolId, $seq)`.
- **Stored in two places** that must match: `users.registration_number` (unique per school: `users_school_registration_unique`) and `student_academics.klassapp_student_id` (`string(20)`, **globally unique**).
- **Checks that assume exactly 7 digits** (`/^KLS\d{7}$/i`):
  - `app/Models/User.php:97` (saving guard on `registration_number`)
  - `app/Models/StudentAcademic.php:101` (saving guard on `klassapp_student_id`)
  - `app/Traits/RegisterUser.php:67` (provided registration number)
  - `app/Http/Controllers/Api/WhatsAppController.php:1035` (a parent typing the ID)
  - `app/Livewire/Superadmin/Academics/UserprofileForm.php:38` and `resources/views/livewire/superadmin/academics/userprofile-form.blade.php:206` (`pattern="KLS\d{7}"`, hint text)
- **Code that reads the number back apart:** `SeedStudentIdSequences` takes the sequence with `SUBSTRING(klassapp_student_id, 7)`, which assumes a 3-digit school part. `DemoStudentCode::toKls()` builds `KLS` + 7 digits and already accepts 7 or 8 digits on the way in (`app/Support/DemoStudentCode.php:18`, not on #1285).

## What goes wrong at the limits

1. **The 10,000th student of one school.** `%04d` does not truncate: sequence 10000 gives `KLS00110000` (8 digits). The `User` and `StudentAcademic` saving guards reject it, so creating that student throws. The sequence counter has already moved on.
2. **A school with id 1000 or more.** `%03d` widens the same way (`KLS10000001`) and fails the same guards.
3. **Plain widening is ambiguous.** If the fix were just to let the ID grow, school 1011 sequence 1 and school 101 sequence 10,001 would both be `KLS10110001`. Because `klassapp_student_id` is unique across all schools, that is a real collision risk, and `SeedStudentIdSequences` would split it wrongly.

## Proposal

**Digits only after `KLS`, no hyphens or spaces. Two forms, told apart by length, never mixed:**

| Form | Layout | Digits | Used when |
| --- | --- | --- | --- |
| Short (today) | `KLS` + school (3) + sequence (4) | 7 | school id is 999 or less **and** the sequence is 9,999 or less |
| Long | `KLS` + school (5) + sequence (6) | 11 | otherwise |

- **Existing IDs never change.** Nothing is migrated or rewritten.
- A school keeps using its existing counter. The sequence number that would be 10,000 is formatted in the long form (see the examples) and the counter carries on. A school id of 1000 or more uses the long form from its first student.
- Because the two forms have different, fixed lengths, no short ID can equal any long ID, and within a form the fields are fixed width, so the mapping from (school, sequence) to ID is one-to-one. That removes the ambiguity in point 3.
- Examples: school 1, sequence 1 is `KLS0010001`. School 1, sequence 10,000 is `KLS` + `00001` + `010000` = `KLS00001010000`. School 1000, sequence 1 is `KLS` + `01000` + `000001` = `KLS01000000001`.
- Capacity: short form up to 999 schools of 9,999 students; long form up to 99,999 schools of 999,999 students each. Column `string(20)` already fits `KLS` + 11 digits (14 characters).

### Changes when it is built (after #1285 lands)

1. **One place defines the format.** Add `StudentIdFormat` (a small class or constants on `StudentIdGeneratorService`) with `format(int $schoolId, int $seq)`, `isValid(string $id)` (`/^KLS(?:\d{7}|\d{11})$/i`) and `parse(string $id)` returning school and sequence, so no regex is repeated (standing rule 40, one implementation per behaviour). Replace the six checks listed above with it.
2. `StudentIdGeneratorService::next()` calls `StudentIdFormat::format()`.
3. `SeedStudentIdSequences` uses `parse()` instead of `SUBSTRING(..., 7)`.
4. **Typed IDs are normalised before matching.** Strip spaces and hyphens and upper-case (`kls-001-0001` → `KLS0010001`) so a parent who types it the way it is written on paper still matches. The stored value is never hyphenated.
5. Form hint and `pattern` accept both forms; the example stays `KLS0010427`.
6. **Printed forms** (report cards, ID cards, letters) keep printing the stored value as is. A hyphenated display, if wanted for readability, is a presentation choice and needs a decision (open question 2).

### Tests (rule 48, named after T-072)

- `test_T072_short_form_is_unchanged`: school 1 sequence 1 is `KLS0010001`; school 999 sequence 9999 is `KLS9999999`.
- `test_T072_sequence_10000_uses_the_long_form_and_saves`: a student at sequence 10000 is created without the saving guard throwing (red on `main`, where it throws).
- `test_T072_school_1000_uses_the_long_form`.
- `test_T072_short_and_long_forms_never_collide`: format every (school, sequence) pair across both boundaries and assert all values are unique.
- `test_T072_existing_ids_are_not_rewritten`: the backfill and seed commands leave stored IDs untouched.
- `test_T072_parse_round_trips`, and `test_T072_typed_id_with_hyphens_matches`.
- Under MySQL with the unique indexes enforced (standing rule 35): two schools cannot produce the same `klassapp_student_id`.

## Open questions for Rasta

1. Is "KLS plus 7 digits while it fits, then a longer form" meant as the two fixed-length forms above, or should the school part be dropped from the long form (a single global sequence)? A global sequence is simpler but would have to start above every existing ID.
2. Should ID cards and report cards show the number grouped for reading (for example `KLS 001 0001`), keeping the stored value unhyphenated?
3. Do any printed forms, School Pay exports or external systems already store the 7-digit form with a fixed-width column?
4. Is 999,999 students per school and 99,999 schools enough headroom for the long form?
