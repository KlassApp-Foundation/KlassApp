# Settings hub

Proposal only. No code in this change. It waits for approval before anyone builds it.

The school admin menu already has a Settings page (`resources/views/admin/settings/index.blade.php`). It is a set of cards that jump to older screens. This proposal replaces that card list with one hub. Site branding and SEO details come off the school settings menu. Maintenance and access stays.

## What exists today

Routes below are under the admin prefix. Line numbers are from `main`.

| Setting | Where it lives | Editability today |
| --- | --- | --- |
| Settings index | `routes/setting.php` lines 4–6, view `admin.settings.index` | Navigation only |
| Site branding (title, logo, favicon) | `routes/setting.php` lines 9–10, `Setting\GeneralController`. Stored on the global `settings` table (`key` is unique; the table has no `school_id` — `database/migrations/2020_02_18_202003_create_settings_table.php`). The same keys are the platform display keys in `SystemSettingsService` | **Platform-wide, not per school.** A school admin saving this form writes the platform title, logo, and favicon for every school. The card mentions a theme; the form has no theme field (`resources/views/admin/settings/general_settings.blade.php`). Layouts link a static KlassApp favicon (`layouts/partials/favicon.blade.php`), not `settings.favicon`. **Remove site branding from the hub.** The school’s own name (`schools.name`) and logo (`school_details` meta `school_logo`) stay on the school profile |
| Maintenance and access (maintenance mode, login, teacher reception, attendance scope) | `routes/setting.php` lines 11–12, `Setting\MaintenanceController` | **Stays in the hub, System section.** `store` writes only the signed-in admin’s school (`School::setDetailValue` for `maintenance`, `login_status`, teacher reception access, and attendance scope). It does not write the global `settings` table. Public registration (`register` / `register_status`) is a platform switch and this controller does not save it |
| SEO details | `routes/setting.php` lines 13–14, `Setting\SeoDetailController`, card at `resources/views/admin/settings/index.blade.php` lines 94–101. Same global `settings` table, no `school_id` | **Platform-wide.** **Remove this card from the school settings menu.** A school admin must not change site description, keywords, or social cards for every school |
| Exam types (which types count toward results) | `routes/setting.php` lines 16–18 | Editable with a rule: a change applies to future results. Released results are not rewritten here |
| Integrations (WhatsApp, Drive, Slack) | `routes/setting.php` lines 20–22 | Disconnect is allowed. Connecting a channel is a separate verified flow |
| Academic years | `routes/setting.php` lines 49–58 | Editable with a rule: the year marked current is the one the rest of the app reads. Do not delete a year that has classes, marks, or fees. Mark it inactive |
| School profile (name, motto, address, logo, country, curriculum, EMIS, UNEB centre) | `routes/admin.php` lines 78–95. Stable entry `GET /admin/school-profile` redirects to the signed-in school’s edit page (lines 90–92) | Name, motto, address, logo, and website are freely editable. Country is editable and, once saved, a change must be confirmed because it changes phone formats and curriculum defaults (see the country draft). Curriculum and EMIS are editable with a rule: they apply to future setup and future results, not to results already released |
| Academic terms and dates | `routes/admin.php` lines 940–945 | Editable with a rule: dates can change until the term has released results. A term in use is not hard-deleted |
| Classes and streams | `routes/setting.php` lines 61–62 (`/admin/standard/create`) and the class structure screens linked from the Settings card “Structure & classes” | Editable with a rule: a class or stream with students, a class teacher, or marks is soft-removed (inactive), not deleted. Refused while it is the only home for those records |
| Subjects | `routes/admin.php` lines 429–436 | Editable with a rule: rename is free. Remove is a soft delete (`restore` exists on line 435). Refused as a hard delete while the subject has marks |
| Fee categories | `routes/admin.php` lines 949–954 | Editable with a rule: amounts can change for future charges. A category with recorded payments is not hard-deleted. Payments are reversed, not edited |
| WhatsApp number | `routes/admin.php` lines 33–39, plus the integrations card | The number is editable. Verification is a separate step. Unlink of a parent (`line 39`) does not delete the parent |
| Plan and subscription | Settings card to `/admin/subscriptions` | Not edited as a school form. Plan changes go through the subscription flow |

`routes/admin.php` lines 462–467 and 1027–1028 are commented copies of the settings routes. The live routes are the ones in `routes/setting.php`.

## What is missing from one hub

These are real school choices. Some have a page somewhere else. None of them are a section of one settings hub with the rules below.

| Item | Today | Proposed rule |
| --- | --- | --- |
| Terms and dates | Separate academic-term screens (`routes/admin.php` lines 940–945). The wizard can also write terms | Editable with a rule. Names and dates change freely until a result for that term is released. After release, dates are not edited on this screen. Reopening a result goes through the marks flow |
| Classes | `/admin/standard/create` (`routes/setting.php` lines 61–62) | Editable with a rule. Soft-remove when the class has no students and no marks. Refused when it is in use, with the list of what uses it |
| Streams | Stored on `standards_link` / `sections`, edited from the class structure screen | Same rule as classes. A stream of 300–500 learners is still one stream row. Soft-remove only when it has no students and no marks |
| Subjects | `routes/admin.php` lines 429–436 | Editable with a rule, as in the table above |
| Curriculum profile | `schools.curriculum`, edited on school details and during setup. There is no profile screen that shows the country’s class, subject, and grading defaults together | Editable with a rule. Changing the profile applies to future classes, subjects, and results. It does not rewrite released results |
| Report composition | Closest control is exam types (`routes/setting.php` lines 16–18): which exam types contribute to the report total | Editable with a rule. Composition applies to future results only. A released report is reopened through the marks flow, not by editing the composition screen |
| WhatsApp | Phone link and integrations, two cards | One section: the school’s number, verification state, and whether parent messaging is on. The number is editable. Message history is not |
| Roles | No school settings screen. Privileges are user-group checks scattered through middleware | Editable with a rule. A role can be granted or revoked. The last school admin cannot be removed. This hub does not invent new platform roles |
| Country | School details, and the onboarding step. The settings form used to fall back to country id 7 when the school had no country | Editable. Uganda is the default while the field is empty. Saving a different country after one is stored requires confirmation, because phone formats and curriculum defaults follow the country |
| Currency | Read from `school_details` meta `currency` (`app/Services/DashboardV2DataService.php` lines 102–115). No settings field was found | Editable. The default comes from the school’s country, not a hard-coded code. Uganda defaults to UGX. Amounts already recorded keep the currency they were stored with. Not a silent rewrite of old payments |
| Timezone | No school setting. Greetings use `app/Support/DashboardGreeting.php` (`timezoneFor`, lines 55–67). A marks release stores its own timezone (`app/Services/MarksRelease/MarksReleaseService.php` `timezone()`, lines 326–330), which today falls back to `Africa/Kampala` | Editable. The default comes from the school’s country. Uganda defaults to `Africa/Kampala`. New deadlines and greetings use the school timezone. Instants already stored stay as those instants |

## One hub

Sections, in this order:

1. **School** — profile (including the school’s own name), country, currency, timezone. Country change confirms. Currency and timezone start from the country (UGX and `Africa/Kampala` for Uganda) and can be changed.
2. **Calendar** — academic years, terms and dates.
3. **Classes** — classes, streams, subjects, curriculum profile.
4. **Results** — report composition and exam types. Applies to future results. Released results reopen through the marks flow.
5. **People** — roles. Student ID numbers are not edited on this hub. A correction is a logged change on the student, not a silent overwrite.
6. **Money** — fee categories. Payments are reversed, not edited.
7. **Messages** — WhatsApp number and verification.
8. **System** — Maintenance and access. This stays. It writes the signed-in school only: maintenance mode, login, teacher reception access, and attendance scope. Public registration stays a platform switch and is not a field on this form.

Site branding (title, favicon, and the theme the card names) is not in this hub. Those values are platform-wide: `GeneralController` saves them through `SettingProcess::updatesettings` into the global `settings` table, which has no `school_id`. The school’s own name and logo stay on the school profile. SEO details are not in this menu for the same reason (`SeoDetailController` writes the same global table). The school hub does not write the global `settings` row.

## What is not edited on the hub

- Released results. Reopen them through the marks flow.
- Payments. Reverse them. Do not edit the recorded payment.
- Student ID numbers. Correct them with a log entry on the student. The hub does not offer a bulk renumber.
