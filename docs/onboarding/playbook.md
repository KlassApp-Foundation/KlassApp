# Onboarding playbook

This is the setup script for a school admin and for the person supporting them. It covers every step in the setup wizard, in the order the wizard asks them. Toshi follows this same script. The summary line on each step is the running summary, so the wizard, Toshi and the dashboard say the same thing.

Exams and SchoolPay are not part of onboarding.

Each step says what to have ready, what the school does, what the engine does, the choices, the starting values, the confirmation text recorded for that step, the summary line the running summary is built from, and every error code with its cause and the fix. The confirmation text is the wording for a confirm card. That card is not in the setup driver yet.

A summary line uses `{tokens}`. The running summary replaces each token from the data saved on the school, one line per finished step. Teachers and students may be skipped for later. WhatsApp can be finished later, and its line is left out until the code matches.

`{year}` means the calendar year. `{next}` means the following year. Those two tokens appear only in the academic-year cause, where the year changes.

A code usually starts with the step name. Codes that start with `whatsapp.` belong to the WhatsApp step (`whatsapp_verify`). Codes that start with `plan.` belong to the plan step (`plan_selection`).

## Generic issue codes

Some codes are built from the step name. `{key}` means that name, as in the step headings below (`standards`, `subjects`, `teachers`, `students`, `terms`).

| Code | Cause | Fix |
| --- | --- | --- |
| `{key}.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `{key}.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `{key}.year` | Create an academic year before this step. | Set the academic year, then return to this step. |
| `{key}.rejected` | The check failed with a message that is not listed on the step. | Correct the value and try again. |
| `{key}.save` | Saving failed with a message that is not listed on the step. | Correct the value and try again. |

Those five steps are the ones that use `.skip`, `.list`, and `.year`. The same three codes are repeated on each of those steps so the cause and the fix sit with the step. Teachers and students can still be skipped; their skip code is only used when a skip is refused.

`.rejected` and `.save` can happen on any step, including `whatsapp_verify` and `plan_selection`. They are not repeated on each step. `.rejected` is a check whose message is not one of the codes below. `.save` is a save that failed with a message that is not one of those codes. If that message is empty, the fix is: Check this answer and try again.

## step: school_name

Question: What is your school's name?

What you'll need: The school's real name. The field is School name. It must be at least 3 characters, and it cannot be a placeholder such as "Ada's School".

The school: Type the school name and confirm it.

The engine: Save the name on the school.

Buttons: none

Choices: none

Default: blank

Confirmation: Confirm the school name, then the engine saves it.

Summary: School name: {name}

### Issues

A placeholder name gets the same words as a blank name. The fix is different.

| Code | Cause | Fix |
| --- | --- | --- |
| `school_name.required` | Enter your real school name. | Type the school's real name. |
| `school_name.min` | The school name must be at least 3 characters. | Enter the full school name, at least 3 characters. |
| `school_name.placeholder` | Enter your real school name. | Replace the placeholder with the school's real name. |

## step: student_size

Question: About how many students does your school have?

What you'll need: An approximate size. The field is Approximate number of students.

The school: Choose a size and confirm it.

The engine: Save the size on the school.

Buttons:
- `Up to 500` — Up to 500
- `Up to 1,000` — Up to 1,000
- `More than 1,000` — More than 1,000

Choices: none

Default: blank

Confirmation: Confirm the size, then the engine saves it on the school.

Summary: School size: {size}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `student_size.choice` | Choose one of the school size options. | Choose Up to 500, Up to 1,000, or More than 1,000. |

## step: country

Question: Which country is your school in?

What you'll need: The country the school is in. The field is Country. The buttons are the countries on file.

The school: Choose the country and confirm it.

The engine: Save the country on the school.

Buttons: countries on file

Fallback:
- `Uganda` — Uganda
- `Kenya` — Kenya
- `Tanzania` — Tanzania

Choices: none

Default: Uganda

Confirmation: Confirm the country, then the engine saves it on the school.

Summary: Country: {country}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `country.required` | Enter a country. | Enter the country name, at least 2 characters. |

## step: curriculum

Question: Which board or curriculum do you follow?

What you'll need: The board the school follows. The field is Board / Curriculum.

The school: Choose the board or curriculum and confirm it.

The engine: Save the curriculum on the school.

Buttons:
- `uneb` — UNEB
- `cambridge` — Cambridge
- `montessori` — Montessori
- `other` — Other

Choices: none

Default: uneb

Confirmation: Confirm the board or curriculum, then the engine saves it on the school.

Summary: Curriculum: {curriculum}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `curriculum.choice` | Choose a curriculum option. | Choose UNEB, Cambridge, Montessori, or Other. |

## step: school_category

Question: What kind of school is this?

What you'll need: The kind of school. The field is School category. This step is asked only when the board is UNEB. The choice creates that kind of school's classes and subjects.

The school: Choose the kind of school and confirm it.

The engine: Save the category and create its classes and subjects.

Buttons:
- `nursery` — Nursery only
- `primary` — Primary
- `primary_nursery` — Primary + Nursery
- `o_level` — O-Level
- `o_a_level` — O-Level + A-Level

Choices: none

Default: blank

Confirmation: Confirm the kind of school, then the engine saves it and creates the classes and subjects.

Summary: School category: {category}

When classes already exist and no category was saved, {category} is already set up.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `school_category.choice` | Choose a school category. | Choose the kind of school from the list. |

## step: emis

Question: What is your EMIS / ministry code?

What you'll need: The school's ministry code. The field is EMIS / Ministry code. This step is asked only when the country is Uganda. The code is at most 20 characters and must not already belong to another school.

The school: Enter the EMIS code and confirm it.

The engine: Save the ministry code on the school.

Buttons: none

Choices: none

Default: blank

Confirmation: Confirm the EMIS code, then the engine saves it on the school.

Summary: EMIS: {code}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `emis.required` | Enter your EMIS / ministry code. | Enter the EMIS or ministry code for this school. |
| `emis.max` | An EMIS / ministry code can be at most 20 characters. | Shorten the code to 20 characters or fewer. |
| `emis.unique` | That EMIS / ministry code is already used by another school. Check the code and try again. | Enter the code that belongs to this school. |

## step: uneb_center

Question: What is your UNEB centre number? (you can skip)

What you'll need: The school's exam centre number, if it has one. This step is asked only when the board is UNEB. Leave the number blank to skip it.

The school: Enter the UNEB centre number, or confirm a blank skip.

The engine: Save the centre number on the school.

Buttons: none

Choices: none

Default: blank

Confirmation: Confirm the centre number, or confirm that it is blank, then the engine saves it.

Summary: UNEB centre: {number}

When the school leaves the number empty, {number} is blank.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `uneb_center.invalid` | Enter a UNEB centre number or skip. | Enter the centre number, or skip this step. |

## step: academic_year

Question: What is the current academic year name?

What you'll need: The year name, plus the dates. The fields are Starts on and Ends on. A description can be left as the usual wording. A typed year is a four-digit year, or a span such as 2026/2027. Replying yes uses the suggested year.

The school: Confirm the year dates.

The engine: Create the academic year.

Buttons: none

Choices: none

Default: The description is Current Academic Year. The start is the first day of this calendar year and the end is the last day of this calendar year.

Confirmation: Confirm the year and its dates, then the engine creates the academic year.

Summary: Academic year: {name}, {start} to {end}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `academic_year.invalid` | Enter an academic year such as {year} or {year}/{next}, or reply yes to use {year}. | Enter a four-digit year, a span such as 2026/2027, or reply yes to use the suggested year. |
| `academic_year.required` | Enter an academic year. | Enter a four-digit year, a span such as 2026/2027, or reply yes to use the suggested year. |
| `academic_year.dates` | Provide both start and end dates. | Enter both the start date and the end date, or leave both blank. |
| `academic_year.end` | The academic year end date must be after the start date. | Move the end date to a day after the start date. |
| `academic_year.date` | The academic year end is not a valid date. | Enter the start and end as real dates, with the end after the start. |

## step: standards

Question: Confirm your classes and streams.

What you'll need: The class names you want, and any streams or class teacher for each class. Classes cannot be skipped. If the category step already created classes, confirm those. Set the academic year first.

The school: Confirm the classes, or add a class, streams, and a class teacher.

The engine: Create the classes and streams.

Buttons: none

Choices:
- `use_this` — Use this
- `adjust` — Adjust

Default: The class name starts as P1 when the school type has no recommended setup. Streams and the recommended setup choice stay blank until a setup applies. Use this lists the classes and streams that would be added and saves nothing. Apply adds exactly that list. The wizard adds that list on Continue only when the school has no classes or streams yet. Continue after that does not add a suggested class or stream. Adjust leaves the step open so the school can clear or change every value. Nothing is saved until the school confirms the step.

Confirmation: Confirm the classes and streams, then the engine creates them.

Summary: Classes: {classes}, {streams}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `standards.min` | Add at least one class. | Add at least one class with a name. |
| `standards.name` | Each class must have a name. | Give each class a name. |
| `standards.duplicate` | Duplicate class name: P.1. | Use each class name only once in the list. |
| `standards.stream_duplicate` | Primary One, stream A is listed more than once. | List each stream once for that class. |
| `standards.stream_exists` | Primary One, stream B: a stream with that name already exists. | Choose a stream name that is not already used in that class. |
| `standards.stream_missing` | Primary One, stream A doesn't exist. | Use a stream that is already on that class, or add it before referring to it. |
| `standards.stream_students` | Primary One, stream A still has students. | Move those students to another class or stream, then remove it. |
| `standards.confirm` | Add or confirm your classes first. | Add at least one class, or confirm once classes are already on the school. |
| `standards.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `standards.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `standards.year` | Create an academic year before this step. | Set the academic year, then return to this step. |

## step: subjects

Question: Confirm subjects for your classes.

What you'll need: The subject names for each class. Subjects cannot be skipped. If subjects are already on the classes, confirm those. Set the academic year first, and add the class before attaching subjects to it.

The school: Confirm the subjects, or add more.

The engine: Create the subjects on each class.

Buttons: none

Choices:
- `use_this` — Use this
- `adjust` — Adjust

Default: Mathematics when the school type has no recommended setup. The recommended setup choice stays blank until a setup applies. Use this fills the usual subjects. Adjust leaves the step open so the school can clear or change every value. Nothing is saved until the school confirms the step.

Confirmation: Confirm the subjects, then the engine creates them on each class.

Summary: Subjects: {count}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `subjects.min` | Add at least one subject. | Add at least one subject. |
| `subjects.class_name` | Class name cannot be empty. | Name the class the subjects belong to. |
| `subjects.min_for_class` | Class 'Primary One' must have at least one subject. | Add at least one subject for that class. |
| `subjects.name` | Subject name cannot be empty. | Give each subject a name. |
| `subjects.missing_class` | Class 'Primary One' does not exist. Add it on the classes step first. | Add the class first, then attach subjects to it. |
| `subjects.confirm` | Add or confirm subjects first. | Add at least one subject, or confirm once subjects are already on the school. |
| `subjects.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `subjects.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `subjects.year` | Create an academic year before this step. | Set the academic year, then return to this step. |

## step: teachers

Question: Add teachers now, or skip for later.

What you'll need: Each teacher's Teacher name, plus an email if you want an account. You can also upload a teacher file and a class teacher file (csv, xlsx, xls, txt, docx, or pdf). You can skip this step and add teachers later. Set the academic year first.

The school: Upload or confirm the teacher list, or skip.

The engine: Create the teacher accounts and link their classes and subjects.

Buttons: none

Choices: none

Default: Nothing is filled in.

Confirmation: Confirm the teacher list, or confirm the skip, then the engine creates the accounts it can.

Summary: Teachers: {teachers}

When the step is skipped, {teachers} is skipped for later.

You can skip this step. The skip code below is only used when a skip is refused.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `teachers.line` | Add a teacher with a name and an email, or type skip. | Add the teacher with a name and an email, or type skip to do this later. |
| `teachers.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `teachers.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `teachers.year` | Create an academic year before this step. | Set the academic year, then return to this step. |

## step: students

Question: Add students now, or skip for later.

What you'll need: Each student's Student name, plus the class. Optional details are stream, gender, date of birth, parent name, parent phone, school student ID, LIN, and UNEB registration number. You can upload a student file (csv, xlsx, xls, txt, docx, or pdf). You can skip this step and add students later. Set the academic year first.

The school: Upload or confirm the student list, or skip.

The engine: Create the students and link parent contacts.

Buttons: none

Choices:
- `female` — Female
- `male` — Male

Default: Nothing is filled in.

Confirmation: Confirm the student list, or confirm the skip, then the engine creates the students it can.

Summary: Students: {students}

When the step is skipped, {students} is skipped for later.

You can skip this step. The skip code below is only used when a skip is refused.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `students.line` | Add a student with a name and a class, or type skip. | Add the student with a name and a class, or type skip to do this later. |
| `students.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `students.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `students.year` | Create an academic year before this step. | Set the academic year, then return to this step. |

## step: terms

Question: Set your academic terms.

What you'll need: The Academic terms, each with a Term name, Starts on, and Ends on, and which one is the Current term. Terms cannot be skipped. Set the academic year first.

The school: Confirm the terms and which one is current.

The engine: Create the academic terms.

Buttons: none

Choices:
- `use_this` — Use this
- `adjust` — Adjust

Default: Term 1 from 3 February to 2 May, Term 2 from 26 May to 29 August, and Term 3 from 22 September to 19 December, in the academic year's calendar year. The current term is Term 1. The recommended setup choice stays blank until a setup applies. Use this fills these terms. Adjust leaves the step open so the school can clear or change every value. Nothing is saved until the school confirms the step.

Confirmation: Confirm the terms and which one is current, then the engine creates them.

Summary: Terms: {terms}, current {current}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `terms.min` | Add at least one term. | Add at least one term with a name, or use the standard three terms. |
| `terms.name` | Term name cannot be empty. | Give the term a name, such as Term 1. |
| `terms.dates` | Term 'Term 1': provide both start and end dates, or neither. | Enter both dates for that term, or leave both blank. |
| `terms.end` | Term 'Term 1': end date must be after start date. | Move the end date to a day after the term starts. |
| `terms.skip` | This step cannot be skipped. | Enter the information for this step. It cannot be skipped. |
| `terms.list` | Provide a valid list for this step. | Send the list for this step, or confirm what is already saved. |
| `terms.year` | Create an academic year before this step. | Set the academic year, then return to this step. |

## step: fees

Question: Add fee structures.

What you'll need: At least one fee. The fields are Fees, Fee name, Amount, and Applies to. A class fee also needs the class. A term fee needs the academic term. A yearly fee covers the whole year. The amount must be greater than zero.

The school: Enter each fee and confirm the list.

The engine: Create the fee structures.

Buttons: none

Choices:
- `whole_school` — Whole school
- `class` — One class

Default: The fee name is Tuition, the amount is 100000, the scope is whole_school, and it is not a yearly fee.

Confirmation: Confirm the fee list, then the engine creates the fee structures.

Summary: Fees: {fees}

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `fees.required` | Add at least one fee structure. | Add a fee with a name and an amount, for example Tuition 250000. |
| `fees.name` | Enter a fee name and an amount. | Enter the fee's name and an amount greater than zero. |
| `fees.amount` | Enter an amount for Tuition. For example: Tuition 250000. | Enter a number greater than zero for that fee, for example Tuition 250000. |

## step: whatsapp_verify

Question: What's your WhatsApp number? We'll send a verification code and link the number only after you enter it.

What you'll need: The WhatsApp number that should receive the code. The field is WhatsApp number. Then the 6-digit code from the message. You can finish setup before this step is done. Parent messages stay off until the number is verified.

The school: Enter the WhatsApp number and confirm the code.

The engine: Send the code and link the number after it matches.

Buttons: none

Choices: none

Default: The phone is blank.

Confirmation: Confirm the WhatsApp number, then the engine sends the code and links the number only after the code matches.

Summary: WhatsApp: {phone}

The summary line is left out until the code matches. Setup can finish before this step is done.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `whatsapp.phone` | Enter a WhatsApp phone number. | Enter the WhatsApp number that should receive the verification code. |
| `whatsapp.user` | A logged-in user is required to link WhatsApp. | Sign in, then enter the WhatsApp number. |
| `whatsapp.phone_first` | Enter your WhatsApp number first. We will send a verification code. | Enter the WhatsApp number that should receive the verification code. |
| `whatsapp.required` | Enter your WhatsApp number. We send a verification code and link it only after you enter that code. | Enter the WhatsApp number that should receive the verification code. |
| `whatsapp.send_failed` | We couldn't send the verification code. Please try again. | Check the number and reply retry, or resend for a new code. |
| `whatsapp.taken` | WhatsApp number +256700000000 is already registered to another account. Use a different number. | Enter a different WhatsApp number that is not already registered. |
| `whatsapp.code` | The verification service returned a message that is not one of the cases above. | Reply resend for a new code, then enter that 6-digit code. |
| `whatsapp.code_mismatch` | That code doesn't match. Try again or send a new code. | Enter the 6-digit code from the WhatsApp message, or reply resend for a new code. |
| `whatsapp.code_expired` | That code has expired. Send a new one. | Reply resend for a new code, then enter that 6-digit code. |
| `whatsapp.code_invalid` | That code is no longer valid. Request a new one. | Reply resend for a new code, then enter that 6-digit code. |
| `whatsapp.code_phone` | Phone number changed after the code was sent. Send a new code. | Send a new code to the current number, then enter that code. |
| `whatsapp.code_missing` | Send a verification code first. | Reply resend for a new code, then enter that 6-digit code. |
| `whatsapp.code_missing` | Verify the code sent to your WhatsApp before continuing. | Reply resend for a new code, then enter that 6-digit code. |

## step: plan_selection

Question: Choose a plan to start with.

What you'll need: The plan the school will start on. The field is Plan. The usual start is Freemium.

The school: Choose a plan and confirm it.

The engine: Save the plan on the school.

Buttons: active plan ids

Choices: none

Default: Freemium when that plan is active, otherwise the first active plan.

Confirmation: Confirm the plan, then the engine saves it on the school. The plan is saved as its number, not as its name.

Summary: Plan: {plan}

A plan typed as text, even when the text is the plan's number, is refused. Choose the plan from the list.

### Issues

| Code | Cause | Fix |
| --- | --- | --- |
| `plan.required` | Choose a plan. | Choose one of the plans in the list. |
| `plan.id_type` | Choose a plan. | Choose a plan from the list. The plan is saved as its number, not as text. |
| `plan.unavailable` | That plan is not available. | Choose a plan that is currently offered. |

## When setup is finished

The plan is the last step. When it is saved, setup is finished.

The school can then invite parents and enter the first marks. Connecting Google tools is offered only when that feature is turned on for the school.

WhatsApp can still be waiting. Parent messages stay off until the school's WhatsApp number is verified.

Exams and SchoolPay stay out of setup.
