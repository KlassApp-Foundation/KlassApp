# Filling the school onboarding checklist

This is the workbook a school sends before it starts using KlassApp. Toshi reads it and sets the school up, one approved step at a time.

The template has **22 tabs**. Do not add or rename tabs. Row 1 is the header, row 2 is a grey example, and data starts on row 3. A column headed with **\*** is required.

## The tabs, in order

| Tab | What it is for |
| --- | --- |
| **School Details** | Field and answer: school name, country, curriculum |
| **Admin & Contacts** | The head teacher and the people KlassApp may contact |
| **Terms & Exams** | The academic year, each term, start and end dates |
| **Classes & Streams** | Every class, its streams, the approximate number of learners, the class teacher |
| **Subjects** | Each subject, whether it counts on the report card, a short code |
| **Teachers** | Each teacher: full name, real email, phone, gender, role |
| **Teaching Assignments** | Which teacher teaches which subject in which class and stream |
| **One tab per class** (Baby Class … Primary Seven in this template) | The learners in that class, with their parent or guardian |
| **All Learners (for import)** | Fills itself from the class tabs. Do not type here |
| **Learners per Class** | A count per class and stream. Compare it with your own numbers |
| **Fees** | Fee name, class, amount, currency, how often it is charged |
| **Confirmations** | The statements the school must agree to before anything is loaded |

Other levels use their own class names. If the school is nursery, primary, secondary O level, secondary A level or a mix, keep the class names the template uses for those levels; do not invent new spellings.

## What is essential, and what can wait

Bring these four lists complete. Without them there is nothing to set up:

- **Classes & Streams** — every class and every stream.
- **Subjects** — at least the subjects that count on the report card.
- **Teachers** and **Teaching Assignments** — every teacher, and who teaches what.
- **Learners and parents** — every learner, with a parent or guardian and a phone number.

These can be finished during onboarding, after the first read:

- Contact details on **Admin & Contacts** beyond the head teacher.
- The **Fees** detail: you can add or change fees in KlassApp later.
- Notes columns anywhere.
- The school's registration or ministry number (EMIS or the local equivalent), if it is not to hand.

## Formats

- **Dates:** `YYYY-MM-DD`, for example `2026-02-02`. The template's example cells show `02/02/2026`; replace them. The reader accepts an Excel date or a date with a month name; a plain `02/02/2026` is not read.
- **Class names:** exactly as the template writes them (`Primary One`, not `P1`; `Baby Class`, not `Baby`).
- **Streams:** one spelling everywhere. If the stream is `East` on Classes & Streams, it is `East` on the class tab and on Teaching Assignments. Do not write `P.1 East` in one place and `East` in another.
- **Phones:** the school's local format, with the leading zero, as a text value: `0712 000001`. Do not add a country code unless the school's numbers are already international.
- **Emails:** real staff addresses. An address that cannot receive mail means that person cannot set a password.
- **Money:** a plain number (`1200`), with the currency in its own column. No symbols, no commas, no "UGX" inside the amount.
- **Learner names:** the name the school uses, spelled the same way on the class tab and in the office records. The KLS number is created by KlassApp; do not type one.

## Privacy

The workbook holds children's names, dates of birth, parent names and phone numbers. Keep it in the school's own storage, not in a personal drive or a chat. Send it only through the onboarding hand-in below. The confirmations tab and the privacy policy and terms must be agreed before anything is loaded.

## Confirmations

On the **Confirmations** tab, the statements must be answered **Yes**:

- We agree to the data processing agreement.
- Contact details are correct.

If either is not Yes, Toshi stops and loads nothing. Fix the answer on the sheet and hand it in again.

## How to hand it in

1. Save or export the workbook as `.xlsx`.
2. In KlassApp, open Toshi and choose **Onboard from checklist**.
3. Either **upload** the file, or click **Choose from Google Drive** and pick it. A Google Sheet is read as Excel; an `.xlsx` stored in Drive is read as it is.
4. Agree to the data processing statement. Toshi does not read the file before that.
5. Read the summary. Anything wrong is listed by tab and row; fix the sheet and hand it in again, or skip those rows.

Nothing is saved until you approve a step.

## Placeholders

- Screenshot: the Confirmations tab with both answers set to Yes.
- Screenshot: the summary card after a read, with one flagged row.
