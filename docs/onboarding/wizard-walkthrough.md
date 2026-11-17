# Wizard walkthrough — after the November 2026 deploy

**Tracker:** T-125. **Owner of the walk:** Rasta. **When:** after the deploy that includes #1285 and T-117, before Kabale is onboarded.

This is the click-through list. It is written for a person using the product, not for a test suite. Every row says what to enter and what should appear. If a row does not appear as described, stop and note the step — do not work around it during the walk.

Use a **fresh test school** (`is_test = 1` is fine, but the wizard is the same for a real school now). Do not use real children's data.

---

## 0. Before you start

| Check | How | Expected |
| --- | --- | --- |
| You are on the deployed commit | `/admin/dashboard` footer or the deploy log | The commit from the deploy, not a cached page |
| The school is unfinished | Dashboard | The "Finish setup" bar is present and names the first unfinished step |
| No Toshi setup path | Dashboard, then `/onboarding/choose` | No "Set up with Toshi" button. `/onboarding/choose` goes straight to the wizard step |
| Exams and SchoolPay are absent | The whole walk | No Exams step, no SchoolPay step, no SchoolPay password field anywhere |

---

## 1. The steps, in order

The wizard opens on the first unfinished step. `?step=<key>` deep-links to a step only when that step is still incomplete.

| # | Step (key) | What to enter | What should appear |
| --- | --- | --- | --- |
| 1 | School name (`school_name`) | A real-looking name, e.g. "Walkthrough Primary" | The name saved; the banner's step count goes up; no placeholder "'s School" accepted |
| 2 | Approximate size (`student_size`) | Pick one of the three cards | The chosen card stays selected after a reload |
| 3 | Country (`country`) | Pick the school's country | The country saved; if the country requires a ministry code, the EMIS step appears next |
| 4 | Board / Curriculum (`curriculum`) | UNEB or the school's board | Curriculum saved; UNEB-only steps (school category, UNEB centre) appear only when UNEB is chosen |
| 5 | School category (`school_category`) | Nursery / primary / secondary / mixed | Saved; later class suggestions follow the category |
| 6 | EMIS / Ministry code (`emis`) | A plausible code, or skip if the country does not require it | Saved; wrong format shows the step's own error, not a generic one |
| 7 | UNEB centre number (`uneb_center`) | A centre number, or leave blank to skip | Saved or explicitly skipped; never blocks a non-UNEB school |
| 8 | Academic year (`academic_year`) | Start and end dates for the current year | Year saved; a second year is not created on reload |
| 9 | Structure & Class Teachers (`standards`) | Accept the suggested classes, add a stream to one class, invite a class teacher | Classes and streams listed; each stream is its own row; a class-teacher invite sends a link (no password in the email) |
| 10 | Subjects (`subjects`) | Add two subjects to one class | Subjects listed against the class; repeat for another class works |
| 11 | Teachers (`teachers`) | Add one teacher by form, then upload a CSV with a deliberately bad row | The good row is added; the bad row is reported with its row number; nothing is half written |
| 12 | Students (`students`) | Add one student by form, then upload a CSV with a missing stream | The student is added; the missing stream is named with a "create stream and retry" action |
| 13 | Academic terms (`terms`) | Accept the three prefilled terms; mark one current | Terms saved; "current" shows on exactly one term |
| 14 | Fee structures (`fees`) | Add one whole-school fee and one class fee | Both listed with the right scope; amounts show the school's currency, not a hard-coded UGX |
| 15 | WhatsApp verification (`whatsapp_verify`) | Enter the school's number, request the code, enter it | Code accepted; the step can be skipped and finished later |
| 16 | Plan selection (`plan_selection`) | Pick a plan | Plan saved; the Finish button becomes available only when no blocking step is left |
| 17 | Review & confirm (`review`) | Read the summary, then Confirm | `onboarding_finished_at` set; the setup bar disappears; the dashboard is the normal one |

Teachers and students are optional for Finish. WhatsApp may be finished later. Everything else blocks Finish.

---

## 2. What was removed and must not appear

| Removed | Where it used to be | What to check |
| --- | --- | --- |
| Toshi's scripted onboarding | Post-signup choice, dashboard button, Toshi panel | No "Set up with Toshi", no chat step questions, no `/create` flow |
| The onboarding choice page | `/onboarding/choose` | It redirects to the wizard; there is no two-card choice |
| Exams | Toshi step and review | No exam step, no exam count in review, no exam created by Finish |
| SchoolPay | Toshi step and school create payload | No SchoolPay step, no API-password field, `school_pay_api_password` unchanged by setup |
| Set-password-at-create for a Toshi-created admin | Toshi create mode | A school is created by signup/invite, not by chat; the admin sets a password through the normal signup/invite path |

Exam results, marks, report cards, fee payments and SchoolPay remain features — they are set up **after** onboarding from their own pages.

---

## 3. After Finish

1. Reload the dashboard: no setup bar, no Toshi setup prompt.
2. Open `/admin/onboarding/wizard` directly: it should show the finished state, not restart setup.
3. Open the school's classes: every stream created in step 9 is listed with its learners.
4. Open one imported student: the KLS number is present, the class and stream are right.
5. Open the teachers list: the invited teacher shows "invite sent", not a password.
6. Open Settings → School details: country, curriculum and EMIS match what was entered.

## 4. If something fails

Note the step number, the exact text on screen, and the URL. Do not fix data by hand during the walk; the point is to find where the wizard and the data disagree. Send the list to the team before Kabale.
