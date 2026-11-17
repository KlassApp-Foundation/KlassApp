# School calendar data

One file per country and year. `uganda-2026.json` is the first. It is data only. No application code reads it.

The country name belongs in these calendar files and in this README. It does not belong in comment text, report-card copy, or other product writing.

## Where the 2026 dates come from

The primary document is the ministry circular **Schools and Other Institutions Calendar for 2026**, dated 24 November 2025, reference ADM/085/157/01.

- Circular PDF: https://www.education.go.ug/wp-content/uploads/2026/01/2026-SCHOOL-CALENDAR.pdf
- Page that links it: https://www.education.go.ug/school-calendars/ (listed there on 6 January 2026)

The PDF is a scan. The term table, the holiday table, the secondary-school activities, the public holidays and the other named days were read from the pages. Two other official PDFs on the same ministry page were read because they speak to Term I:

- Observance letter, 6 January 2026: https://www.education.go.ug/wp-content/uploads/2026/01/OBSERVANCE-OF-THE-OFFICIAL-CALENDAR-FOR-EDUCATION-INSTITUTIONS-FOR-THE-ACADEMIC-YEAR-2026.pdf
- Reopening announcement, 16 January 2026: https://www.education.go.ug/wp-content/uploads/2026/01/OFFICIAL-ANNOUNCEMENT-ON-THE-REOPENING-OF-ALL-SCHOOLS-AND-EDUCATION-INSTITUTIONS-1.pdf

`retrieved_date` is 2026-11-15, the day those three PDFs were downloaded.

News sites were not used for any date.

## How `number_of_days` is counted

`day_count_method` in the JSON is the rule:

Count every calendar date from the start date through the end date, **including both ends**. Weekends count. Public holidays count. The circular says the same thing: the days it records include public holidays and weekends.

Check against Third Term, which the circular prints as Monday 14 September 2026 to Friday 4 December 2026, 82 days:

- 14–30 September is 17 days
- October is 31
- November is 30
- 1–4 December is 4
- 17 + 31 + 30 + 4 = 82

The same count gives 89 for Term II (25 May 2026 through 21 August 2026) and 89 for the calendar's Term I span (2 February 2026 through 1 May 2026). The 16 January announcement's span (10 February 2026 through 1 May 2026) counts to 81. The announcement does not print 81. That figure is only a count of the dates it prints. It is not stored as `number_of_days`.

The product owner's known check is the same Third Term span and the same 82 days. The circular agrees. Nothing was changed to force the check.

## Term dates

| Term | Opening | Closing | Days | Status |
| --- | --- | --- | --- | --- |
| Term I | not chosen | 2026-05-01 | not chosen | Opening conflicts. See below. Closing agrees. |
| Term II | 2026-05-25 | 2026-08-21 | 89 | Calendar only. No second official date was found. |
| Term III | 2026-09-14 | 2026-12-04 | 82 | Calendar and the product-owner check agree. |

### Term I conflict

Do not treat either opening as the chosen one. `opening_date` and `number_of_days` are null. The `conflicts` array holds both.

| Opening | Days as stated | Source |
| --- | --- | --- |
| Monday 2 February 2026 | 89, printed | Calendar, 24 November 2025, primary and secondary table |
| 2 February 2026 | not printed | Observance letter, 6 January 2026. It names this opening and the 1 May closing, and it does not restate Term II or Term III. |
| Tuesday 10 February 2026 | not printed | Reopening announcement, 16 January 2026. It says Term 1 runs from 10 February to 1 May. |

All three sources agree that Term I closes on Friday 1 May 2026.

### 2nd term holiday, printed year disagrees with the printed day count

The holiday table prints **22nd August, 2025** to **13th September, 2026**, and prints **23 days**.

- 22 August 2025 through 13 September 2026 inclusive is 388 days, not 23.
- 22 August 2026 through 13 September 2026 inclusive is 23 days.
- The printed holiday total is 104, which is 23 + 23 + 58.

`start_date` for that break is null. `end_date` is 2026-09-13, which the table prints as 2026. `number_of_days` keeps the printed 23. This file does not pick a start year.

The 1st term holiday (2–24 May 2026, 23 days) and the 3rd term holiday (5 December 2026–31 January 2027, 58 days) match an inclusive count.

## What was not copied

The same PDF prints calendars for health training, skills centres, technical and farm institutes, and teacher colleges, plus a national physical education and sports list. Those are different institutions. They are not in this file. The sports pages could not be read row by row from the scan, so no sports row was guessed.

The primary and secondary section does not date a national examination period. The circular says some activity dates are sent later. None were invented.

Public-holiday rows print a day and a month and no year. The year is stored as 2026, and each note says so. Good Friday is printed as 29 March and Easter Monday as 1 April. In 2026 those calendar dates are a Sunday and a Wednesday. The printed dates are kept.

Rows that print a month or a term and no full date have null ISO dates and keep the printed label. That includes the science fair ("July") and the agriculture show ("7th – 12th July").

The S.1 and S.5 table is headed "TERM 1 2025" while its rows print 2026. The row years are stored. The heading is recorded in the notes. Placement of S.1 students (29–30 January 2026) is before both Term I openings, so it sits in the root `national_activities` list rather than inside Term I.

## Schema

A later country or year is a new file beside this one, named with that country and year, with this shape. Keys are required unless the required column says no. `null` is allowed only where the type says so.

### Root

| Field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `country` | object | yes | The country this file is about. |
| `country.name` | string | yes | Common name. |
| `country.iso_code` | string | yes | ISO 3166-1 alpha-2. |
| `year` | integer | yes | The school year the terms belong to. |
| `source_url` | string | yes | URL of the official circular or calendar used as the primary source. |
| `source_title` | string | yes | Title printed on that document. |
| `retrieved_date` | string `YYYY-MM-DD` | yes | Day the source was read. |
| `circular_date` | string `YYYY-MM-DD` or null | no | Date printed on the circular, when it has one. |
| `reference` | string or null | no | File reference printed on the circular. |
| `listing_url` | string or null | no | Official page that links the circular, when there is one. |
| `day_count_method` | string | yes | How `number_of_days` was counted, in words. |
| `terms` | array of term objects | yes | At least one term. |
| `public_holidays` | array of holiday objects | yes | The official public-holiday table. May be empty. |
| `other_named_days` | array of activity objects | yes | Other days the circular names that are not public holidays and not term breaks. May be empty. |
| `national_activities` | array of activity objects | yes | Activities that are not inside one term. May be empty. Activities that fall in a term also live on that term. |
| `notes` | array of strings | yes | Reader notes. May be empty. |

### Term

| Field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `name` | string | yes | Name as printed (`Term I`, `Term II`, `Term III`, or that country's own names). |
| `opening_date` | string `YYYY-MM-DD` or null | yes | First day. Null when sources disagree or the date was not read. |
| `closing_date` | string `YYYY-MM-DD` or null | yes | Last day. Null when unknown or in conflict. |
| `number_of_days` | integer or null | yes | Inclusive day count when both dates are chosen and the source states or the count confirms a single figure. Null when a date is unresolved. |
| `conflicts` | array of conflict objects | yes | Empty when there is no disagreement. |
| `known_check` | object or omitted | no | A date a product owner asked to check. Present only when one was given. |
| `known_check.opening_date` | string `YYYY-MM-DD` | when `known_check` is present | |
| `known_check.closing_date` | string `YYYY-MM-DD` | when `known_check` is present | |
| `known_check.number_of_days` | integer | when `known_check` is present | |
| `known_check.label` | string | when `known_check` is present | Says who gave the check and whether the circular agrees. |
| `holidays` | array of holiday objects | yes | Breaks after the term, and public holidays inside the term or inside that break. |
| `national_activities` | array of activity objects | yes | Activities the circular places in this term or in the break after it. |

### Conflict

| Field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `field` | string | yes | Which term field disagrees, usually `opening_date`. |
| `value` | string or null | yes | The date this source prints. |
| `stated_number_of_days` | integer or null | yes | The day count that source prints, or null if it prints none. |
| `source_url` | string | yes | Official document for this value. |
| `source_title` | string | yes | Title of that document. |
| `source_date` | string `YYYY-MM-DD` or null | yes | Date printed on that document. |
| `note` | string | yes | What the source says, including any count that was not printed by the source. |

### Holiday

| Field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `name` | string | yes | Name as printed. |
| `kind` | string | yes | `term_break` or `public_holiday`. |
| `start_date` | string `YYYY-MM-DD` or null | yes | Null when the printed text cannot be turned into one date without choosing. |
| `end_date` | string `YYYY-MM-DD` or null | yes | Same as `start_date` for a single day. |
| `date_label` | string | yes | The date as printed, kept even when ISO dates are filled. |
| `number_of_days` | integer or null | yes | Printed day count, or 1 for a single public holiday. |
| `placement` | string | yes | `inside_term`, `between_terms`, `before_first_term`, or `overlaps_term_and_break`. |
| `source_url` | string | yes | Document this row was read from. |
| `note` | string or null | yes | Year assumptions, disagreements, or why a date is null. |

### Activity (national activities and other named days)

| Field | Type | Required | Meaning |
| --- | --- | --- | --- |
| `name` | string | yes | Name as printed. |
| `start_date` | string `YYYY-MM-DD` or null | yes | Null when the circular gives no full date. |
| `end_date` | string `YYYY-MM-DD` or null | yes | Null for a single undated label. Equal to `start_date` for one day. |
| `date_label` | string | yes | The date words as printed (`1st Term`, `July`, `13th March 2026`). |
| `placement` | string | yes | Same placement values as a holiday. |
| `source_url` | string | yes | Document this row was read from. |
| `note` | string or null | yes | Why a date is null, or which term the row falls in. |

Dates are ISO `YYYY-MM-DD`. Text is UTF-8. A file that cannot be read from an official source should leave the unknown dates null and say so in `notes`, rather than filling them from a news report.
