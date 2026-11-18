# Report-card configuration and the Toshi report skill

Status: design for review. Not built. Not committed. Nothing in this file is a live school.

Checked against `origin/main` at `169c092c`. The grading table, the division table, and the release freezes named below are already on that commit. The configuration object is not. Drafts #1372 and #1373 are not part of this design and are not a reason to change it.

The card is one configuration per school. Kabale Junior School is the first worked example. Lakeside Day School, further down, is an invented second school. The sheet in `designs/lakeside-day.html` is that second configuration drawn out. If a third school needed its own branch in the renderer, this schema would be wrong.

Every number on a card still comes from the report service and the results service. The configuration decides what is asked for, what is printed, and which subject ids are handed to the division function. It does not add, weight, rank, or round.

## 1. Schema

One record per school, scoped by `school_id` on every read and write. Class identity is `section_id`. A stream is one `standards_link`. `standard_id` is the grading band, never the class. Learners, subjects, and teachers are stored and loaded by id. A display name is a label.

`schema_version` is an integer. This document is version 1.

### Report kind

| Field | Default | Validation |
| --- | --- | --- |
| `report_kind` | `progressive` | One of `progressive`, `set_based`. Same two values as `ReportKindSetting::OPTIONS`. |

`progressive` is the formal card: a results table and an end-of-term table. `set_based` means each exam set has its own report, and a combined report uses the weights already stored on the exam-type mix.

### Sections

Each section is on or off. At least one of `monthly_ledger`, `set_columns`, and `end_of_term_ledger` must be on.

| Field | Default | Validation |
| --- | --- | --- |
| `monthly_ledger` | off | Boolean. Allowed only when `report_kind` is `progressive`. A `set_based` school that turns it on is rejected. |
| `set_columns` | on | Boolean. Subjects down, sets across. This is the arrangement Formal, Warm, and New look already print. |
| `end_of_term_ledger` | on | Boolean. Subjects down, one combined mark per subject. |
| `combined_line` | on | Boolean. When on, the combined mark is the figure the report service already returns for the mix. The template does not add the columns. |
| `grading_key` | off on a parent card, on for a sample pulled for the school to approve | Boolean. The key is the school's saved grading table and division table. It is not a third copy of the bands. |

Kabale turns the monthly ledger on and the set columns off. Lakeside does the opposite. Both can turn the end-of-term ledger on or off without a new template.

### Header fields

Each field is `{ "enabled": true|false }`. A disabled field is omitted. The header does not keep an empty label. Sources that already exist stay the source. New text settings start empty. The form prefills nothing that claims day or boarding, a box number, or a telephone the school has not stored.

| Field | Default | Source and validation |
| --- | --- | --- |
| Crest | on | `school_details.school_logo`. No logo: the school's initials. Not a typed image on the card. |
| School name | on | `schools.name`. Required when enabled. |
| Subtitle | off | New text, max 120 characters. Empty until the school writes it. |
| P.O. Box | off | New text, max 120 characters. Not `schools.pincode`. |
| Telephone | on | `schools.phone` and `school_details.landline_no`, the line the card already joins. No number is stored in this configuration. |
| Motto | on | `schools.motto`, then `school_details.moto`. |
| Serial | off | Optional. When off, no "Serial" label. What the serial identifies is still an open question in `02-field-spec.md`. |

### Term wording

The stored term name stays whatever the school saved. The card prints a phrase. The default phrases, used when the school has not written its own, are:

| Term order | Printed phrase |
| --- | --- |
| 1 | End of First Term |
| 2 | End of Second Term |
| 3 | End of Third Term |

Validation: one phrase per term the school has, each 1–60 characters. Extra terms need their own phrase. The phrase does not replace `academic_terms.name`. The academic year printed beside it is the year name, not a second store.

### Monthly ledger

On only when `monthly_ledger` is on.

Rows are not typed. A row is the calendar month of the sitting's exam date (`scheduled_at`). The mark in the cell is that sitting's approved mark.

| Rule | Validation |
| --- | --- |
| A sitting with no date | Does not print. It is listed for a person to date. The card does not guess a month. |
| Two included sittings in one month | Two rows. Each row is the month plus that set's own name. This is the current rule. Question 1 in the field spec is still open, so the configuration stores `same_month: "two_rows"` and `confirmed: false` until the school answers. |
| A set the mix marks `include: false` | Does not print. |
| A set that is not released | Does not print, once any started set for that class and term is released. Until then, every started set prints. This is the print rule already on `main`. It is not a school toggle. |
| A counting set | Prints as its own month row and still feeds the end-of-term combined total. Marks Gained stays the report-service total. The template does not add the month row into that total again. Kabale stores this as `counting_set_also_prints_as_month_row: true` with `confirmed: false`. Ignored when the monthly ledger is off. |

Columns are the subjects that level offers, in the level's order. English, Mathematics, Science, and Social Studies, when they are in the division list, each have an aggregate column. Other subjects have a mark and no aggregate column. Then Total, Agg, Div, Psn. Total is that sitting's own total from the results service. Agg and Div are that sitting's core aggregate and division. Psn is calculated. The teacher does not type it.

The monthly grid may use a subject's `short_label`. The legend prints `label` in full. MDD is only a short label.

### End-of-term ledger

On only when `end_of_term_ledger` is on. Rows are the offered subjects, in the level's order. Columns are Subject, Full Marks, Marks Gained, Agg, Comment, Initials.

Marks Gained is the weighted total the report service already returns. One counting set at weight 100 prints that mark, including a decimal when the service returns one. Several counting sets print the one combined figure.

### Subjects, per class level

The list is for the level (`standard_id` as the band), for the whole school. It is not per child, and it is not "whoever happened to receive a mark". A child who offers a subject on the list with no approved mark prints Missing once the set is approved and released. A subject left off the list does not appear.

Starter list for a lower primary level, which the school edits: Literacy I, Literacy II, English, Mathematics, Local Language, Science, Social Studies, Music, Dance and Drama, P.E., Arts and Tech. Upper primary starts from English, Mathematics, Science, and Social Studies. The starter is a prefill. It is not a hardcoded syllabus.

Each subject:

| Field | Default | Validation |
| --- | --- | --- |
| `subject_id` | required once the school has subjects | The subject's id in this school. Unique in the level list. Never matched by name alone. |
| `label` | the subject's name | 1–80 characters. Unique in the level list. |
| `short_label` | none | 1–12 characters when set. The monthly grid uses it. The end-of-term row and the legend use `label`. |
| `order` | the list order | Integers, unique in the level. |
| `full_marks` | 100 | Integer, at least 1. Default 100 because that is what Formal prints today. Whether a school ever uses another number is still open. |
| `in_division` | true for English, Mathematics, Science, and Social Studies when those names are on the list | Boolean. |

`division_subject_ids` is the ids with `in_division: true`. It must be a subset of the level's offered ids. The results service receives only that set when it sums points for the aggregate and the division. Other offered subjects still print, and on the end-of-term ledger they still have their own aggregate letter. An empty division list leaves the aggregate empty. It does not fall back to every subject. The code on `main` still sums every subject that has points. A build passes this list in. It does not write a second sum.

A learner's own offered set is a subset of the level list. The teacher can remove one the learner does not take. They cannot add a subject the level does not offer unless an admin changes the level list.

### Grading and division tables

Reuse what is on `main`. Do not store a second copy in this configuration.

Primary defaults in `config/grading_uganda.php`, which a school edits on the grading screen: D1 95–100 is 1, Excellent; D2 90–94 is 2, V.Good; C3 75–89 is 3, Good; C4 65–74 is 4, F.Good/Q.Good; C5 60–64 is 5, Promising; C6 50–59 is 6, Fair; P7 45–49 is 7, Work hard; P8 40–44 is 8, Aim higher; F9 0–39 is 9, More effort.

Division defaults from `ProgressiveRules::defaultDivisionRanges()`: 12 or less is 1, 13–24 is 2, 25–28 is 3, 29–32 is 4, 33 or more is U. A saved `ReportDivisionRange` for that school and level replaces the default. `ThresholdDivisionRule` and `RangeDivisionRule` both ignore a separate "how many subjects" argument, so "12 or less" stays the label. With exactly four subjects the lowest aggregate is 4, and "12 or less" matches a "4–12" division 1 only in that case. The product keeps "12 or less".

### Footer lines

Each line is on or off, with the label the school prints. Defaults:

| Key | Default label | Default | Source |
| --- | --- | --- | --- |
| `position_in_class` | Position in class | on | Calculated. Every stream of that level. "5 out of 28 pupils". Ties share a place. |
| `position_in_stream` | Position in stream | on | The same ranking, scoped by `section_id`. |
| `next_term_begins` | Next term begins | on | The next term's `starts_on`. |
| `next_term_ends` | Next term ends | on | The next term's end date. |
| `class_teacher_comment` | Class teacher's report | on | A paragraph. The comment bank, once wired, offers six lines. The teacher can type. |
| `class_teacher_signature` | Class teacher | on | The class teacher's name under a line. |
| `head_teacher_comment` | Head teacher's report | on | The head's paragraph. |
| `head_teacher_signature` | Head teacher | on | The head's name under a line. |
| `stamp` | Sign and stamp | on | The uploaded stamp, or the words "Sign and stamp" and an empty box. |

Labels are 1–40 characters. A disabled line is omitted. Position is never typed into the configuration. Sample ranks on the drawings are layout only.

Questions 2, 3, 4, and 8 in the field spec are still open (class versus stream, who writes the subject comment, who types the next-term dates). The defaults above are the current proposal. `confirmed` on those four keys stays false until the school answers.

### Skin

| Field | Default | Validation |
| --- | --- | --- |
| `skin` | `formal` | One of `formal` (Standard), `warm` (Warm), `progressive` (New look). The three keys in `StudentReportCardService::TEMPLATES`. |

The skin is the ink, the type, and the frame. The arrangement is the sections above. Kabale's three drawings are one arrangement in three skins. Adding those drawings as three more templates would duplicate the arrangement. Lakeside uses `formal` with the monthly ledger off, so a reader can see that the skin and the arrangement are different switches.

## 2. What is deliberately not configurable

These stay out of the schema so a school setting cannot fork the arithmetic or show a mark the release rules are there to hide.

- Totals, weights, aggregate points, division, and rank. They are computed by `ResultsService`, `ProgressiveRules`, and `StudentReportCardService`. A template that adds its own weights recreates the 326 versus 326.4 bug. The configuration names the subject ids that enter the division. It does not contain a formula.
- Rounding. The service rounds. A subject cell keeps the floor the PDF already prints. The total line keeps the service total, including the decimal.
- The print gate. A set prints when it is approved, released, and the mix has not set `include: false`, with the rule already on `main`: until any started set is released, every started set prints. A school cannot turn on "show unreleased marks" for a card that goes out.
- A third way to treat a blank. The release is `missing_as_zero` or `skip_blanks`. An approved blank under `missing_as_zero` prints Missing and counts as zero. ABS on an approved subject prints ABS and counts as zero. An unapproved subject prints a dash and stays out of that set's total.
- Grading bands and division ranges as a private table inside this object. They are edited on the screens that already save `school_grading_systems` and `report_division_ranges`. This schema points at those rows.
- Month-row labels as free text. The month comes from the exam date. The set name comes from the exam type. The school dates the exam or renames the set. It does not type "February" onto a sitting that was sat in May.
- Learner, class, subject, and school identity by name. Lookups use the id, and the school id, under the uniqueness rule that already fixed a cross-school name collision.
- Turning Toshi on. A new school stays in `preview` with `toshi_enabled` 0 and the assistant off. Saving a report-card configuration does not set those flags. The skill below runs only after a person has opted that school in.
- Anything about another school. A configuration, a preview, and an audit row are all scoped by `school_id`.

## 3. Versioning

A released report freezes its template version and its rules. The rules already freeze. The template version does not, yet.

On release, `main` already copies these once, and a second call leaves the first copy in place:

- `report_kind`, via `ReportKindSetting::freeze`. A reprint uses `forRelease`. A later change applies from the next release.
- `scoring_rule`, via `ReportScoring::freeze`.
- `progressive_rules`, via `ProgressiveRulesStamp::freeze`. A reprint reads the copy. A release from before the column existed is not rewritten.
- `report_composition`, via `ReportComposition::freezeOn`.

`schools.report_template` is not copied onto the release. Changing the skin today would change the next print of a report that has already been released. This schema adds one more copy, beside those four, not instead of them:

`report_card_configuration` on the release: `schema_version`, `skin`, the section switches, the header and footer switches and labels, the term phrases, the level subject lists (ids, order, labels, short labels, full marks, division flags), and the two unconfirmed flags (`same_month`, `counting_set_also_prints_as_month_row`). Grading bands and division ranges stay in the progressive-rules copy. The mix stays in `report_composition`. The kind stays in `report_kind`.

A second freeze leaves the first copy. A later edit of the school configuration applies to the next release. A reprint reads the copy. A release from before this column existed is not rewritten, and it keeps the skin it has today until a person re-releases.

## 4. The Toshi report skill

The skill is the school's configuration, plus the guided fill, plus natural-language edits. It is off until the school has opted in. Saving a configuration does not opt the school in.

### Guided fill

The order is section 8 of `02-field-spec.md`. Each save of a mark goes through `ExamMarkWriter`. The chat does not write a mark by another path. The class teacher walks one learner. An admin can do every step. A head teacher follows the admin path inside their own school.

1. Class. Chips of the classes this person heads. Confirm the stream (`standards_link` id). An admin sees every class in the school.
2. Term. Prefill the current term and year. Show the phrase this configuration will print. Ask them to confirm.
3. Learner. Names with their ids on screen. The save uses the learner id. Two learners with the same name stay two chips. The skill does not pick one.
4. Subjects. The level list from this configuration. Say which of them this learner offers. The teacher can remove one. They cannot add a subject the level does not offer.
5. Sets. The school's sets for this term, each with its date, whether it counts, and whether it is included. Name the ones that will not print, and why. Offer to date a set that has no date.
6. Pick a set to fill. Default to the earliest included set that is still open. Then one subject at a time, in the level's order. Read the mark back with the grade and the points from the grading service. A reply of Missing is an approved blank only after the set is approved.
7. Read the row back. Total, aggregate, and division come from the services. Say that position is calculated when the class is released. Do not ask for it.
8. Next set, or comments. A counting set still asks for each subject's mark. When the monthly ledger is on, say that it will also print as its month row. That double placement stays unconfirmed while the flag says so.
9. End of term, when that ledger is on. Show Marks Gained from the service. Ask for the comment, prefilled with the grade remark. Ask for the initials, prefilled from the subject teacher. Read the line back.
10. Class teacher's report, when that footer line is on. Offer the six bank lines for the learner's band once the bank is wired. The teacher picks one or types. Then the signature.
11. Stop for a class teacher. The head teacher adds their report, signature, and stamp.
12. Admin only. Approve or return each subject. A return needs a reason. Release the set. Head teacher's report, signature, and stamp. Then open the card. The card still hides any set the print rule hides.

The chat never asks the teacher to type a total, an aggregate, a division, or a position.

### Natural-language edits

A sentence is a proposed change to one allowed field, or a mark through `ExamMarkWriter`. The skill shows a preview of the card, or of the one line that will change, and waits for an explicit confirmation. The first reply does not write.

Allowed without leaving this skill: a header or footer switch or label, a term phrase, a subject label or short label, full marks, whether a subject is in the division list, a section switch, the skin, a mark, a comment, initials. Each of those is a field in this schema or a mark the writer already accepts.

Refused, with a short reason and no write:

- "Make her total 400", or any instruction to store an aggregate, a division, or a rank. The preview can show the figure the service returns. It cannot accept a replacement.
- A change to a release that already froze its configuration. The skill says the change will apply from the next release.
- A learner chosen by name when more than one id matches.
- A subject that is not on the level list.
- Turning the assistant on, or editing another school's configuration.

The preview's numbers are loaded from the services after the proposed mark is applied in the preview only. They are not arithmetic in the prompt.

### Audit

Every confirmed write stores an audit row in that school: the actor's id, `school_id`, the learner id when the change is a learner's mark or comment, the field, the value before, the value after, and the time of the confirmation. A mark's audit is the one `ExamMarkWriter` already leaves. A configuration change uses the same shape as `SchoolReportKindChange`: who, which school, from, to. A refused instruction is not an audit row. A preview is not an audit row. Nothing is written before confirmation.

## 5. Kabale Junior School, as data

This is the configuration for the card in `02-field-spec.md` and the three drawings. Sample marks, the layout positions, and the placeholder address lines stay in that spec. They are not repeated here. `confirmed: false` means the school has not signed that point off. The drawings still use it so the layout can be judged.

```json
{
  "schema_version": 1,
  "school_key": "kabale-junior",
  "report_kind": "progressive",
  "sections": {
    "monthly_ledger": true,
    "set_columns": false,
    "end_of_term_ledger": true,
    "combined_line": true,
    "grading_key": "sample_only"
  },
  "header": {
    "crest": { "enabled": true },
    "school_name": { "enabled": true },
    "subtitle": { "enabled": true, "text": "Nursery and Primary Day and Boarding" },
    "po_box": { "enabled": true },
    "telephone": { "enabled": true },
    "motto": { "enabled": true, "text": "Hard Work Pays" },
    "serial": { "enabled": true, "confirmed": false }
  },
  "term_phrases": {
    "1": "End of First Term",
    "2": "End of Second Term",
    "3": "End of Third Term"
  },
  "monthly_ledger": {
    "same_month": "two_rows",
    "same_month_confirmed": false,
    "counting_set_also_prints_as_month_row": true,
    "counting_set_confirmed": false
  },
  "levels": [
    {
      "level_key": "lower_primary",
      "subjects": [
        { "key": "literacy_1", "label": "Literacy I", "order": 1, "full_marks": 100, "in_division": false },
        { "key": "literacy_2", "label": "Literacy II", "order": 2, "full_marks": 100, "in_division": false },
        { "key": "english", "label": "English", "order": 3, "full_marks": 100, "in_division": true },
        { "key": "mathematics", "label": "Mathematics", "order": 4, "full_marks": 100, "in_division": true },
        { "key": "local_language", "label": "Local Language", "order": 5, "full_marks": 100, "in_division": false },
        { "key": "science", "label": "Science", "order": 6, "full_marks": 100, "in_division": true },
        { "key": "social_studies", "label": "Social Studies", "order": 7, "full_marks": 100, "in_division": true },
        { "key": "mdd", "label": "Music, Dance and Drama", "short_label": "MDD", "order": 8, "full_marks": 100, "in_division": false },
        { "key": "pe", "label": "P.E.", "order": 9, "full_marks": 100, "in_division": false },
        { "key": "arts_tech", "label": "Arts and Tech", "order": 10, "full_marks": 100, "in_division": false }
      ]
    }
  ],
  "grading": "school_grading_systems, primary defaults on main until the school edits them",
  "division": "ProgressiveRules::defaultDivisionRanges until the school saves a range",
  "footer": {
    "position_in_class": { "enabled": true, "label": "Position in class", "confirmed": false },
    "position_in_stream": { "enabled": true, "label": "Position in stream", "confirmed": false },
    "next_term_begins": { "enabled": true, "label": "Next term begins" },
    "next_term_ends": { "enabled": true, "label": "Next term ends" },
    "class_teacher_comment": { "enabled": true, "label": "Class teacher's report" },
    "class_teacher_signature": { "enabled": true, "label": "Class teacher" },
    "head_teacher_comment": { "enabled": true, "label": "Head teacher's report" },
    "head_teacher_signature": { "enabled": true, "label": "Head teacher" },
    "stamp": { "enabled": true, "label": "Sign and stamp" }
  },
  "skin": "formal"
}
```

`subject_id` is omitted until this school exists as rows. `key` is the stable token in the design. A build replaces each key with that school's subject id and drops the key. The sample learner in the drawings offers eight of the ten: Literacy II and Arts and Tech are off her card. That subset is the learner's offered list, not a second school configuration.

The three drawings are this object with `skin` set to `formal`, `warm`, and `progressive`. The sections do not change between them.

## 6. Lakeside Day School, as data

Invented. Not a customer. No address line and no telephone, because those fields are off. The sample learner and the three sets are fixtures so the sheet can be checked against the primary table. They are not a roster. Positions on the sheet are layout samples.

`report_kind` is `set_based`, so the monthly ledger is off and the validation in section 1 would reject turning it on. There is no end-of-term ledger and no combined line. Sets are columns. Division uses four subjects, and they are not Kabale's four. Creative Arts is offered, prints, and stays out of the division. The grading table and the division table are the ones on `main`. This school did not bring its own.

```json
{
  "schema_version": 1,
  "school_key": "lakeside-day",
  "report_kind": "set_based",
  "sections": {
    "monthly_ledger": false,
    "set_columns": true,
    "end_of_term_ledger": false,
    "combined_line": false,
    "grading_key": false
  },
  "header": {
    "crest": { "enabled": true },
    "school_name": { "enabled": true },
    "subtitle": { "enabled": true, "text": "Primary day school" },
    "po_box": { "enabled": false },
    "telephone": { "enabled": false },
    "motto": { "enabled": true, "text": "Learn and serve" },
    "serial": { "enabled": false }
  },
  "term_phrases": {
    "3": "Third term, 2026"
  },
  "monthly_ledger": null,
  "levels": [
    {
      "level_key": "primary_five",
      "subjects": [
        { "key": "reading", "label": "Reading", "order": 1, "full_marks": 100, "in_division": true },
        { "key": "number_work", "label": "Number Work", "order": 2, "full_marks": 100, "in_division": true },
        { "key": "religious_education", "label": "Religious Education", "order": 3, "full_marks": 100, "in_division": true },
        { "key": "computer_studies", "label": "Computer Studies", "order": 4, "full_marks": 100, "in_division": true },
        { "key": "creative_arts", "label": "Creative Arts", "order": 5, "full_marks": 100, "in_division": false }
      ]
    }
  ],
  "grading": "primary defaults on main",
  "division": "ProgressiveRules::defaultDivisionRanges",
  "footer": {
    "position_in_class": { "enabled": true, "label": "Place in the form" },
    "position_in_stream": { "enabled": false },
    "next_term_begins": { "enabled": true, "label": "School reopens" },
    "next_term_ends": { "enabled": false },
    "class_teacher_comment": { "enabled": true, "label": "Form teacher's note" },
    "class_teacher_signature": { "enabled": true, "label": "Form teacher" },
    "head_teacher_comment": { "enabled": true, "label": "Head's note" },
    "head_teacher_signature": { "enabled": true, "label": "Head" },
    "stamp": { "enabled": false }
  },
  "skin": "formal",
  "sample": {
    "learner": "Tendo Mukasa",
    "class_label": "Primary Five",
    "stream": "North",
    "sets": [
      {
        "name": "Check 1",
        "marks": { "reading": 70, "number_work": 80, "religious_education": 60, "computer_studies": 50, "creative_arts": 75 },
        "total": 335,
        "aggregate": 18,
        "division": "2"
      },
      {
        "name": "Check 2",
        "marks": { "reading": 74, "number_work": 85, "religious_education": 64, "computer_studies": 58, "creative_arts": 78 },
        "total": 359,
        "aggregate": 18,
        "division": "2"
      },
      {
        "name": "Term Examination",
        "marks": { "reading": 81, "number_work": 90, "religious_education": 66, "computer_studies": 62, "creative_arts": 84 },
        "total": 383,
        "aggregate": 14,
        "division": "2"
      }
    ],
    "place_in_form": "4 out of 30 pupils",
    "place_is_layout_sample": true,
    "school_reopens": "18 January 2027"
  }
}
```

Check against the primary table, for Term Examination: Reading 81 is C3 (3), Number Work 90 is D2 (2), Religious Education 66 is C4 (4), Computer Studies 62 is C5 (5). Those four sum to 14, which is division 2. Creative Arts 84 is C3 and is not in the 14. The total 383 is the five marks added. Check 1 is 4+3+5+6 = 18. Check 2 is 4+3+5+6 = 18. Both are division 2. The sheet prints those grades beside the marks. It does not invent a scale.

`designs/lakeside-day.html` is this object drawn as one page. Set-based reports are one card per set in the product. The drawing places the three sets in columns so the missing monthly ledger is visible in one glance. There is no month row, no MDD, no "End of Third Term", and no combined decimal line.
