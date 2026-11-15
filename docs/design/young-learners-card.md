# Young learners report card

`resources/views/reports/young-learners-preview.html` is a static preview for nursery and lower primary. It is not wired to a route, a controller, or a Blade template. The learner and the school on it are made up.

The page is A4 portrait and is meant to stay on one sheet. Type is DM Sans for text and Sora for the school name, the learner's name, and the section titles. The accent is `#15803D` and the text is `#1E293B`, the same pair as the progressive report. A tint or a border never carries the meaning on its own: every result is also written as a word. The sheet still reads in black and white.

The preview shows grade words instead of an aggregate, a division, or a position. It has a comment from the class teacher and one from the head teacher, plus strengths and next steps.

## Fields the card needs

| Field | On the preview | In the code today |
| --- | --- | --- |
| School name | Meadow Learning House | Exists. `schools.name`, read for the card in `StudentReportCardService::resolveSchoolIdentity()` (`app/Services/StudentReportCardService.php`, the `name` key in the array returned near line 838). |
| Motto | Curiosity, kindness, and care. | Exists. `School::mottoText()` at `app/Models/School.php` line 23, passed through as `motto` in the same identity array. Hidden when empty, as on the progressive report. |
| Crest, or a monogram of the initials | Monogram `MH` | The logo file exists as school-detail meta `school_logo` (`School::schoolDetailLogo()` at `app/Models/School.php` line 164). A monogram when the logo is missing is not drawn by the current formal template. The preview uses a monogram only. |
| Document title | PROGRESS REPORT | New wording for this variant. The formal template uses its own title. |
| Term and year | Term Two, 2026 | Term dates exist on `academic_terms`. The formal card already loads the next term at `StudentReportCardService.php` line 601. |
| Learner name | Rowan Ellis | Exists. `User` display name, printed on the formal card as `$learner->displayName` (`resources/views/admin/marks/report-templates/formal.blade.php` line 304). |
| Learner number | MH-0142 | Exists. `users.registration_number` (`app/Models/User.php` fillable list, and the admission-number accessor near line 430). Omitted on a real card when the learner has none. |
| Class | Year Two | Exists. The formal card receives `$class_name` from the section name (`StudentReportCardService.php` line 592). |
| Attendance in words | Present on nearly every school day | Attendance rows exist (`app/Models/Attendance.php`, table `attendances`: `user_id`, `date`, `session`, `status`). The report card does not read them today. A count, or a sentence derived from that count, is new on this card. The preview uses a sentence and shows no figure. |
| Learning area | Five areas, in words | Partly there for nursery. `nursery_assessments.domain` (`app/Models/Academics/NurseryAssessment.php` lines 14–20; migration `database/migrations/2026_07_06_030000_create_nursery_assessments_table.php` lines 16–18). The formal nursery block lists four domains: Literacy, Numeracy, Motor Skills, Social/Emotional (`formal.blade.php` lines 318–328). Lower primary still prints marks. A shared list of areas for nursery and lower primary is new. |
| Grade word | Excellent, Very good, Good, Developing, Needs support | Nursery has a free-text `rating` column (the migration comment names Excellent, Good, Satisfactory, Needs Improvement). It is not this five-word scale, and lower primary does not use it. The word scale is new. |
| What we saw | One short line per area | Nursery `remarks` exists on the same row. Using it as the line under each area is a new use. Lower primary has no remarks column on the report. |
| Strengths | One short block | New. No column or comment section stores this. |
| Next steps | One short block | New. No column or comment section stores this. |
| Class teacher's comment | A short paragraph | Exists as the view key `teacherComment` (`StudentReportCardService.php` lines 578–580 and 621). Label on the formal card at `formal.blade.php` line 464. |
| Head teacher's comment | A short paragraph | Exists as `headTeacherComment` (lines 581–583 and 622). Label at `formal.blade.php` line 469. |
| Class teacher name on the signature | The line is blank | The class teacher is `sections.class_teacher_id`. The formal card does not print that name in the signature. Printing it would be a new use of an existing link. |
| Head teacher name on the signature | The line is blank | No head-teacher name field was found on current `main`. The line stays blank. |
| Parent or carer signature | A blank line | New on this layout. The formal card has class teacher and head teacher lines. |
| Next term begins | A date | Exists. `nextTerm` at `StudentReportCardService.php` line 601, printed on the formal card when the learner is not in nursery (`formal.blade.php` lines 453–455). |

Aggregate, division, and position are left off on purpose. The formal card already hides the aggregate for nursery and for a standard named `primary_lower`, unless `grading_style` is `aggregate` (`StudentReportCardService.php` lines 441–445). This variant never shows them.

## Grade words from marks

This map is an example only. Each school confirms its own words and its own cut-offs. The comment-bank bands are a separate choice and are not these cut-offs.

The example uses the learner's average mark for the term, after the school has decided how a missing mark counts.

| Average | Word |
| --- | --- |
| 90 and above | Excellent |
| 75 to 89 | Very good |
| 60 to 74 | Good |
| 45 to 59 | Developing |
| below 45 | Needs support |

Nursery can ignore marks and store the word directly in `nursery_assessments.rating`. Lower primary can derive the word from marks with the school's own map. Either way the card prints the word, not the figure.

## What this file does not do

It does not change `formal.blade.php`, `warm.blade.php`, or any route. A later change would add a template key and pass the fields above. Until then, opening the HTML file in a browser is the only way to see it.
