# Demo data: two fictional schools for onboarding walkthroughs

Two complete, **fictional** schools, laid out as pre-onboarding checklists for the Wizard
(`app/Livewire/ManualOnboardingWizard.php`). Every name is invented, every email ends in
`@example.com`, and every phone number is in the obviously fake `+256 700 000 xxx` range
(it still passes the Uganda rule in `config/phone_countries.php`: `+256` and 9 national digits).

| Folder | School | Category | EMIS | Classes | Teachers | Students |
|---|---|---|---|---|---|---|
| `sunrise-primary-and-nursery-school/` | Sunrise Primary and Nursery School | Primary + Nursery | DEMO-0001 | Baby, Middle, Top Class; Primary One to Seven (streams A/B on P1, P3, P5) | 15 | 120 |
| `hilltop-o-and-a-level-school/` | Hilltop O and A Level School | O-Level + A-Level | DEMO-0002 | Senior One to Four (streams A/B); Senior Five and Six with combinations PCM, BCM, HEG, MEG as streams | 25 | 200 |

Both use UNEB, Uganda, academic year 2027 (2027-01-01 to 2027-12-31) and the same terms:
Term 1 2027-02-03 to 2027-05-02 (current), Term 2 2027-05-24 to 2027-08-22, Term 3 2027-09-13 to 2027-12-05.

## Files in each school folder

| File | Workbook sheet | Wizard step | How it is used |
|---|---|---|---|
| `<slug>.xlsx` | all sheets below plus **Checklist** | all | The whole checklist in one workbook. The Checklist sheet lists every answer for the first steps (name, size, country, curriculum, category, EMIS, UNEB centre, academic year), the terms, the admin and the WhatsApp number. |
| `classes-and-streams.csv` | Classes and Streams | Classes (`standards`) | Typed, not uploaded. The classes are seeded by the school category; add each listed stream with **Add stream** on its class. A blank Stream means the class has no streams. |
| `subjects.csv` | Subjects | Subjects | Typed, not uploaded. "Seeded by KlassApp" is already there after the academic year step; pick the class and paste "Add in the Wizard" into the subjects box, then **Add**. |
| `teacher-upload-template.csv` | Teacher Upload | Teachers, "Upload teachers" | Upload. Same columns as `public/templates/teacher-upload-template.xlsx`: `Name, Email, Subjects, Classes, Phone`. Subjects and Classes are comma-separated; every subject listed exists on every class listed. |
| `class-teacher-upload-template.csv` | Class Teacher Upload | Teachers, "Upload class teachers" | Upload **after** the teacher file. Same columns as `public/templates/class-teacher-upload-template.csv` (route `admin.class-teachers.upload-template`): `class, stream, name, email, phone`. Every row reuses a teacher from the teacher file (matched by email), so no extra teachers are created. |
| `student-upload-template.csv` | Students | Students, "Upload students" | Upload. Same columns as the per-school template from `StudentUploadTemplateService` (route `admin.students.upload-template`): `Name, Class, Stream, Gender, Parent Name, Parent Phone, School Student ID, LIN, UNEB Reg No., Date of Birth`. LIN is left blank on purpose (LINs are unique nationally). |
| `fees.csv` | Fees | Fees | Typed, not uploaded: one **+ Add fee** per row. "Applies to" is `Whole school` or `One class`; Term is `Term 1`/`Term 2`/`Term 3`, or `Yearly` (tick "Yearly fee"). |

The Wizard has no upload for classes and streams, subjects or fees, so those three sheets mirror the Wizard's form fields.
`public/templates/fee-upload-template.xlsx` is a payments file for the legacy Toshi flow, not fee structures, so it is not used here.

## Upload the CSVs, not the workbook

Upload the CSV for each step. The upload reader takes the workbook's active sheet, or the sheet whose
headers match best when the active sheet is the Checklist, so the combined workbook always reads as the
Teacher Upload sheet. If you prefer xlsx, copy one sheet into its own file first.

## Order for a Wizard walkthrough

1. Answer the first steps from the Checklist sheet. On the academic year step, type `2027`, `2027-01-01` and `2027-12-31`.
2. Classes: add the streams from `classes-and-streams.csv`.
3. Subjects: for each class, add the "Add in the Wizard" subjects.
4. Teachers: upload `teacher-upload-template.csv`, then `class-teacher-upload-template.csv`.
5. Students: upload `student-upload-template.csv`.
6. Terms: the step prefills the current year's dates. Remove the three prefilled terms and add the 2027 dates, then mark Term 1 current.
7. Fees: add each row of `fees.csv`.
8. WhatsApp: use the number on the Checklist sheet and the code from the WhatsApp log or test channel.

## Things to know

- **Fee names include the term** ("Tuition Term 1"). Fee categories are unique on school, level, class and name, so the
  same name for one class in two terms keeps only the first one.
- **UNEB registration numbers** (`U0000/nnn`, a fake centre) are filled only for Primary Seven, which has no streams.
  Streamed candidate classes (Senior Four A/B, Senior Six PCM…) are left blank because the importer only keeps a
  UNEB number when the class name is exactly a candidate class.
- **Parent Name** is in the student file because the template has it, but onboarding currently keeps only the parent
  phone (saved as the student's phone).
- Emails and EMIS codes are unique across the whole platform. Before running the same school a second time on one
  environment (for example Hilltop through Toshi after the Wizard), remove the first copy with `test:purge-schools`.

## Checked against the real importers

Each school was run through the real Wizard component (Livewire test on a fresh in-memory database), once with the
CSVs and once with each workbook sheet saved as its own xlsx. All four runs gave 0 skipped rows and 0 errors:

| | Sunrise | Hilltop |
|---|---|---|
| Classes / streams added | 10 / 6 (16 class records) | 6 / 16 (22 class records) |
| Subject records (per class and stream) | 100 (40 seeded) | 276 (50 seeded) |
| Teachers / teacher–subject–class links | 15 / 60 | 25 / 76 |
| Class-teacher assignments | 13 | 16 |
| Students (all placed in the right class and stream, with parent phone, gender, ID and date of birth) | 120 | 200 |
| Terms (2027 dates, Term 1 current) | 3 | 3 |
| Fee entries / fee rows saved (whole-school fees save one row per level) | 35 / 39 | 24 / 28 |
| WhatsApp verified (wrong code refused first) | yes | yes |

`_generator/generate.py` rebuilds every file (`python3 docs/demo-data/_generator/generate.py docs/demo-data`, needs `openpyxl`).
