# Report-card comment bank

`defaults.json` is a starter set of comment options for a report card. It is data only. Nothing in the application reads it yet.

The file is UTF-8 JSON, pretty-printed, shaped as:

```json
{"<section>": {"<band>": ["text", "..."]}}
```

## Where the sections come from

The cards on current `main` have two comment sections. There is no comment model, no comment-bank migration, and no seeder. The text is chosen at render time and passed into the template under two keys.

| JSON key | What it is | Where it was found |
| --- | --- | --- |
| `teacherComment` | Class teacher's comment | `app/Services/StudentReportCardService.php` assigns it at lines 578–580 and passes it to the view at line 621. `app/Services/ReportCardCommentService.php` `commentFor()` starts at line 43 and reads `config/report_card_comments.php`. The formal card prints the label `CLASSTEACHER'S COMMENT` at `resources/views/admin/marks/report-templates/formal.blade.php` line 464, and the value at line 465. The warm card does the same at lines 427–428. The older student report prints `Class Teacher` at `resources/views/admin/marks/student-report.blade.php` line 308 and the value at line 309. |
| `headTeacherComment` | Head teacher's comment | `StudentReportCardService.php` assigns it at lines 581–583 and passes it at line 622. `ReportCardCommentService.php` `headTeacherCommentFor()` starts at line 78 and reads `config/report_card_head_comments.php`. The formal card prints `HEADTEACHER'S COMMENT` at line 469 and the value at line 470. The warm card does the same at lines 432–433. The older student report prints the label `Head Teacher` at line 312 and leaves the box empty. |

The visible labels use an apostrophe (`CLASSTEACHER'S COMMENT`, `HEADTEACHER'S COMMENT`). The JSON keys are the view keys, `teacherComment` and `headTeacherComment`, because those are the names the service and the templates already share. There is no stored key called `class_teacher` or `head_teacher`.

The existing config files group phrases by `lower` and `upper`, and each group is a list of `{min, max, comments}` score ranges. Those ranges are not copied here. This file does not set a mark threshold for any band.

## What each band key means

The band is a name for the kind of comment. **The mark thresholds for each band are not set here. They are for the product to decide.** A school, or a later setting, chooses which average maps to which band. This file only holds the words.

| Band key | Meaning |
| --- | --- |
| `excellent` | The term's work is strong throughout, and the comment can say so plainly. |
| `very_good` | The work is strong, with a small next step still worth naming. |
| `good` | The learner is taking part and making progress. The comment names one useful gain. |
| `needs_support` | The learner needs a clearer, smaller next step and a bit more help. The tone stays warm. |
| `urgent_support` | The learner needs the adults at home and at school to plan support together. The comment asks for that. It does not blame. |
| `missing_marks` | Some results are absent. The comment stays neutral and does not invent a result. One option in each section is exactly: `Some marks are missing for this term; please check with the school.` |

## How many options

Each section has six options in each of the six bands. Six is above the floor of five so a school can turn one off and still have five left.

## Writing rules

Each option is one or two sentences.

- Warm, specific, and never harsh.
- No learner's name.
- No gendered pronouns. The learner is `they`.
- No country, school, or exam-board name.
- No marks and no numerals in the text.
- No promise about promotion or about a move to another class.
- The options in a band are different remarks, not one sentence rewritten.

`teacherComment` talks about the classroom: how the learner joined in, finished work, and treated other people. `headTeacherComment` talks about the term as a whole. The two banks do not share sentences, so a card can show both without repeating itself. That matches the collision guard in `ReportCardCommentService::headTeacherCommentFor()`, which refuses to print the same phrase in both boxes.

## Rules from the 16 November decisions

These rules are for the product. This file does not enforce them.

- A school edits, disables, and reorders its own options.
- A school never keeps fewer than five active options in a section.
- Once a card is released, it keeps the text that was on it. Later edits to this bank do not rewrite a card that has already gone out.
