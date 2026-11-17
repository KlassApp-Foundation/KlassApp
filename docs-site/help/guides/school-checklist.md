# Filling the school onboarding checklist

KlassApp keeps one copy of this checklist per school in Google Sheets, made from the blank template. Fill that copy in; do not make your own workbook. It collects everything KlassApp needs to set up the school in one go. Fill it once, send it back, and the KlassApp team sets up the school from it.

## Before you type

- Work through the tabs in the order they appear: School Details, Admin & Contacts, Terms & Exams, Classes & Streams, Subjects, Teachers, Teaching Assignments, then the learners (one tab per class), then Fees and Confirmations.
- Type only in the **yellow cells**. Green column headers marked **\*** are required. Blue headers are optional but helpful.
- **On the list tabs, row 2 is a grey example row.** Leave it or delete it; KlassApp ignores it, and data starts on row 3. **School Details** and **Confirmations** have no example row: they start on row 2. **All Learners (for import)** also starts on row 2.
- **Do not type on Progress, Learners per Class or All Learners (for import).** They fill themselves.
- Do not add, rename, delete or reorder tabs, and do not insert columns.

## The tabs

| Tab | What it is for |
| --- | --- |
| **Instructions** | The rules, the column guide and the formats. Read it first |
| **Progress** | How much is complete. Read only |
| **Learners per Class** | Learners, boys and girls per class. Read only |
| **School Details** | A form: the item in column A, the school's answer in column B |
| **Admin & Contacts** | The main admin, head teacher and anyone else who needs dashboard access |
| **Terms & Exams** | The academic year and each term's start and end dates |
| **Classes & Streams** | Every class and stream, the approximate number of learners, the class teacher |
| **Subjects** | Each subject, its section, which classes take it, whether it counts on the report card |
| **Teachers** | Every teacher: name, real email, phone, gender, role |
| **Teaching Assignments** | One row per teacher, class, stream and subject |
| **Baby Class … Primary Seven** | One tab per class with that class's learners and their parents |
| **All Learners (for import)** | Combines the class tabs. Read only |
| **Fees** | Fee name, class, amount, currency, how often it is charged |
| **Confirmations** | The statements the school must answer before data is loaded |

## School Details

It is a form, not a table. Column A names the item; column B takes the answer. Answer every row marked with **\***:

School name (as on report cards) · School level(s) · Country · District / town · School email · School phone · Curriculum · Head Teacher's full name.

These are optional but helpful: short name or initials, day or boarding, physical address, EMIS number, UNEB centre number, school motto, Head Teacher's email.

The template's examples use Uganda (`UG`), `UGX` and Ugandan phone numbers. If the school is elsewhere, write its country and use its own local phone format; put the currency on Fees.

## Admin & Contacts

| Column | What goes in it |
| --- | --- |
| Role **\*** | `Main school admin` manages the school on KlassApp. Add others who need dashboard access |
| Full name **\*** | First name then surname |
| Email **\*** | A real, working address. The invitation to set up the account goes there; no password is sent |
| Phone **\*** | The school's local format, one number per cell |
| WhatsApp on this number? | `Yes` or `No` |
| Notes | Anything the school wants to add |

## Terms & Exams

| Column | What goes in it |
| --- | --- |
| Academic year **\*** | For example `2027` |
| Term **\*** | `Term 1`, `Term 2` or `Term 3` |
| Start date **\*** | `YYYY-MM-DD`, for example `2027-02-08` |
| End date **\*** | `YYYY-MM-DD` |
| Notes | Optional |

Add one row per term. Three terms is the usual pattern.

## Exams / assessments (below the terms)

The same tab has a second block, below the terms, headed **Exams / assessments you give each term**. Fill one row per assessment:

| Column | What goes in it |
| --- | --- |
| Assessment name **\*** | For example `End of Term` |
| Classes it applies to **\*** | `All`, or list the classes |
| Out of (marks) **\*** | A whole number |
| Counts on the report card? **\*** | `Yes` or `No` |
| Notes | Optional |

Do not move this block or put term rows inside it. The terms go above; the assessments go here.

## Classes & Streams

| Column | What goes in it |
| --- | --- |
| Class **\*** | Pick from the list. Use the exact name: `Primary One`, not `P.1` or `P1`. A different spelling creates a duplicate class |
| Stream | Leave blank if the class has no streams. **One row per stream** |
| Number of learners (approx.) | A whole number |
| Class teacher full name | The teacher who heads this class or stream |
| Class teacher email | They receive the invitation to head it |
| Notes | Optional |

A class with two streams has two rows, with the same class and different streams.

## Subjects

| Column | What goes in it |
| --- | --- |
| Subject or learning area **\*** | The subject name, spelled as the school uses it |
| Section **\*** | `Nursery`, `Lower Primary (P.1 to P.3)` or `Upper Primary (P.4 to P.7)` |
| Classes that take it **\*** | `All in section`, or list the classes, for example `Primary Four, Primary Five` |
| Counts on report card total? **\*** | `Yes` or `No` |
| Short code | For example `MTC`, `ENG`, `SST`, `SCI` |
| Notes | Optional |

## Teachers

| Column | What goes in it |
| --- | --- |
| Full name **\*** | First name then surname |
| Email **\*** | A real, working address. The account invitation goes there; no password is sent |
| Phone **\*** | The school's local format, one number per cell |
| Gender | `Male` or `Female` |
| Role **\*** | `Teacher`, `Head Teacher`, `Deputy`, `Director of Studies` or `Other` |
| Notes | Optional |

## Teaching Assignments

One row per teacher, per class (and stream), per subject. A teacher who teaches Mathematics in Primary One East and Primary One West has two rows.

| Column | What goes in it |
| --- | --- |
| Teacher full name **\*** | The same spelling as on the Teachers tab |
| Class **\*** | Pick from the list |
| Stream | Leave blank if the class has no streams |
| Subject **\*** | The same spelling as on the Subjects tab |
| Notes | Optional |

## The class tabs

One tab per class. The tab name is the learner's class; do not add a Class column. Start at row 3.

| Column | What goes in it |
| --- | --- |
| Learner full name **\*** | First name then surname, as on the register |
| Stream | Leave blank if the class has no streams; otherwise exactly as on Classes & Streams |
| Gender **\*** | `Male` or `Female` |
| Date of birth | `YYYY-MM-DD` |
| Parent / guardian full name **\*** | First name then surname |
| Parent / guardian phone **\*** | The school's local format, one number per cell. Siblings share the same parent phone |
| Relationship | `Mother`, `Father`, `Guardian` or `Other` |
| Parent / guardian email | Optional |
| Second parent / guardian name | Optional |
| Second parent / guardian phone | Optional |
| Existing school ID number | If the school already numbers learners |
| Notes | Optional |

## Bringing learners in from a file

Do not retype learners you already have in Excel, a CSV or another Google Sheet. The Instructions tab gives the rule; this is the same rule in steps:

1. Open the class tab and click cell **A3**.
2. Paste with **Edit → Paste special → Values only**, **one column at a time**, so each column lands under its matching heading. Leave out any Class column: the tab name is the class.
3. To bring a file in: **File → Import → Upload**, then choose **Insert new sheet(s)**. Copy from that new tab, paste as above, then delete the new tab.
4. **Never choose "Replace spreadsheet".** It deletes every tab in the checklist.

## Fees

| Column | What goes in it |
| --- | --- |
| Fee name **\*** | For example `Tuition`, `Lunch`, `Transport`, `Development` |
| Class **\*** | Pick a class, or write `All` |
| Amount **\*** | Numbers only, no commas or currency signs, for example `450000` |
| Currency **\*** | For example `UGX` |
| Duration **\*** | `Termly` (most fees), `One-off` or `Yearly` |
| Compulsory? | `Yes` or `No` |
| Notes | Optional |

## Confirmations

Answer **Yes** or **No** in column B for each statement. A **Yes** is required for:

- The school agrees to sign KlassApp's data processing agreement digitally before data is loaded.
- The school has read KlassApp's privacy policy and terms of use.

The other statements should also be true before the file is returned: parents have been told the school is moving to KlassApp and may link on WhatsApp; stream names are written the same way on every tab; every staff member listed has a working email address.

If the data processing statement is not Yes, KlassApp stops and loads nothing.

## Formats

- **Class names:** exactly as in the dropdown (`Primary One`, not `P.1`).
- **Streams:** one spelling everywhere (`East`, `West`).
- **Dates:** `YYYY-MM-DD` (`2027-02-08`).
- **Phones:** the school's local format, one number per cell, for example `0772 123456`.
- **Emails:** real, working addresses. No passwords are sent.
- **Money:** numbers only, no commas or currency signs (`450000`).

## Privacy

This file contains personal information about learners, parents and staff. Share it only with the KlassApp onboarding team, and do not post it in group chats. Parents will not receive WhatsApp messages from KlassApp unless they start the conversation themselves.

## Returning it

When it is complete, send it back to your KlassApp onboarding contact. If anything is unclear, leave a note in the Notes column or on the Confirmations tab.

## Common mistakes

- Typing in **All Learners (for import)** instead of the class tabs. It fills itself and is ignored.
- Adding a **Class** column to a class tab. The tab name is the class.
- Writing `P.1` or `P1` instead of `Primary One`; this creates a duplicate class.
- Writing a stream two ways (`East` on one tab, `P.1 East` on another).
- Leaving the **Currency** column blank on Fees.
- Using **Import → Replace spreadsheet**, which deletes every tab. Use **Insert new sheet(s)**.
- Typing a date as `08/02/2027` instead of `2027-02-08`.

## Placeholders

- Screenshot: School Details filled in.
- Screenshot: Confirmations with the required answers set to Yes.
