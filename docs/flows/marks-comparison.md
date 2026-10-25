# Marks blueprint vs marks-flow spec

Blueprint: [`marks.json`](marks.json), exported from the n8n workflow "KlassApp Blueprint: Marks" (41 nodes, inactive, read once on 2026-10-26 02:32 EAT).
Spec: [`docs/product/marks-flow.md`](../product/marks-flow.md) on `main` at `ac36ca66`, the version approved on 2026-10-25. Open PR #1234 changes that file. Redo this comparison if it merges or the blueprint changes.

This is a design comparison only. It changes no code and no spec.

## What was stripped from the export

`settings.errorWorkflow`, the workflow id, `versionId`, `meta.instanceId` (empty in the export), every node's `webhookId` (5 nodes: the form trigger and four form waits), every `credentials` block (there were none), and pinned data (there was none). Instance-only fields were also left out: sharing scopes, `canExecute`, `activeVersion`, `parentFolderId`, timestamps, and the trigger-info text. Node ids, positions, parameters, connections, settings, and node groups are kept so the file imports cleanly.

## PII check

No real people. Every name is synthetic ("Demo Pupil 1–6", "Demo Parent A/B", "Demo Class/English/Mathematics/Science Teacher", "Junior Demo School"). The five email addresses all use the reserved `demo.invalid` domain. Parent `phone` is `null`. No phone numbers, URLs, tokens, or keys. Nothing needed replacing.

## Disagreements (blueprint vs spec)

1. **No Meta template-approval gate on the WhatsApp send.** Blueprint: `System: check WhatsApp switch` goes straight to `WhatsApp: send results_released template to approved, linked parents`. Spec: the first flowchart has `Results template approved by Meta` between the switch and the send. Decision 3 and phase 6 say nothing goes to a real school until that template is approved, and with the switch on before approval the release still opens portal, Toshi and report cards and sends nothing.
2. **No "release past sittings" action.** Blueprint releases only the current sitting (`Admin: release sitting`), and nothing shows old sittings staying hidden. Spec: Decision 4, step 6, phase 6 and the `past` node in the flowchart need a separate "release past sittings" action with its own confirmation.
3. **The combined sheet appears only after every subject is approved.** Blueprint: `System: check all subjects approved` (true) → `Admin and class teacher: view combined mark sheet`. When any subject is not approved, the flow ends at `System: block release until all approved` and staff see no sheet. Spec ("The flow" and step 5): staff can open the sheet and the report card while the sitting is still in review, marked "Not released". Only *release* waits for all approvals.
4. **No staff report-card preview.** Blueprint has no node for it. Spec: in review, staff can open the report card, marked "Not released" (see the "Report card preview" in the second flowchart and step 7).
5. **Only the subject teacher enters marks.** Blueprint: `Subject teacher: enter or upload marks`, `Note: subject teacher`. Spec step 2: the subject teacher, the class teacher, or the school admin, plus a Toshi mark, all through `ExamMarkWriter`. Phase 2 names the class teacher explicitly.
6. **The admin is not the only place to assign a class teacher.** Blueprint: `Note: assignment` says "This is the **only** place to assign", and `Note: school admin` says "only place to do it". Spec step 1, Decision 9 and phase 1: the admin dashboard is the screen, but the wizard, Toshi and the invite form call the same assignment service.
7. **Inviting a teacher has no invite step.** Blueprint: `Admin: assign class teacher` collects `New teacher email`, and `Admin: record class teacher assignment` stores `invitedEmail`. Nothing sends or tracks an invite. Spec step 1 and phase 1: "invite a new one" is part of the one assignment service.
8. **The log note covers less than the node.** Blueprint: `Note: assignment` says "Replacing a current class teacher is logged", but `Admin: record class teacher assignment` logs every assignment. Spec Decision 9: pick, invite and replace each write a log entry. The node matches the spec. The note under-states it.
9. **Class-teacher release does not check "own class".** Blueprint: `System: check who may release` lets `Releasing as = Class teacher` through whenever `classTeacherCanRelease` is on. It does not check that the person is P.4's class teacher. The role itself is a self-chosen dropdown on `Admin: release sitting`. Spec Decision 1 and step 6: a class teacher releases "their own class" only. Roles are authorised, not declared. This is acceptable in a blueprint, but the build must not copy it.
10. **WhatsApp recipients are not limited to the released class.** Blueprint: `System: record sitting released` sets `whatsappRecipients` to every approved parent with any linked student in the school. It ignores `whatsappOptIn` and does not filter to students in P.4 / this sitting. Spec step 8 and Decision 3: approved, linked parents of the students in the released sitting (scoped through `ReleaseGate` and the school).
11. **The send is always a template.** Blueprint: the template is the only push path. Spec ("Inbound MARKS, GRADES, and REPORT…" paragraph and step 8): the push "uses a template when the 24-hour window is closed". Inside the window it can be a normal reply. The blueprint is simpler. The spec's window rule has no node.
12. **Only GRADES is answered on WhatsApp.** Blueprint: `Parent: replies GRADES or taps See results, receives the marks`, `Note: GRADES reply`. Spec (second flowchart, step 8): inbound MARKS, GRADES and REPORT all stay behind `ReleaseGate`. REPORT also returns the report-card PDF through `StudentReportCardService`.
13. **The family side only covers parents.** Blueprint: `Parent: portal shows results`, `Parent: report card available`, `Note: portal` ("linked parents"). Spec ("The flow"): parents, students, alumni, Toshi, family report-card PDFs and WhatsApp all read through the gate. The second flowchart has "Parent and student portals". `Note: Toshi` mentions students, but no student or alumni portal node exists.
14. **No lock or reopen after approval.** Blueprint: once `Admin: approve subject` runs, nothing locks or reopens a subject, and nothing happens after release. Spec step 4 and phase 3: `MarksSubmissionService` owns lock, approve, reject (return) and reopen.
15. **An uploaded file has no parse or preview step, and it is not matched by registration number.** Blueprint: `Subject teacher: save marks as draft` only labels `markSource` "Uploaded file (to be parsed)", and the marks it saves come from the typed fields, which are all blank when a file is uploaded. Spec step 2 and "Rules that already hold": `MarksImportService` parses and previews, then calls `ExamMarkWriter`. The import matches the registration number, not the display name.
16. **The results template wording and button are not in the spec on `main`.** Blueprint: `Note: WhatsApp template` names `results_released`, gives its body ("Hello {{1}}, {{2}} has released the {{4}} results for {{3}}. Tap See results or reply GRADES…") with variables 1 parent, 2 school, 3 student, 4 sitting, and the "See results" quick reply. Spec ("WhatsApp results templates", "What is missing"): the template is still described as missing, with no name and no button. Its variables are listed as parent name, student name, sitting label, school name, in a different order from the blueprint. The spec should be updated to the submitted template. The blueprint matches what was submitted.
17. **Nursery wording.** Blueprint: `Note: not in this version` lists "Nursery ratings" as out of scope. Spec Decision 7 and step 2: nursery ratings stay on the existing ratings form, unchanged. They are not out of scope. The intent is close, but the wording differs.

## In the blueprint, not in the spec

- A "return without a reason" fallback: `Admin: route review decision` → `Admin: ask for return reason` → back to `Admin: review subject`. The spec says only that a return stores a reason.
- `System: refuse release (not permitted)` as an explicit refusal outcome.
- The per-release WhatsApp switch defaults to **No** (`Send results on WhatsApp`). The spec gives no default.
- File types limited to `.csv` and `.xlsx`. The spec says only "uploaded file".
- The parent's ability to tap **See results** (handled like GRADES), and GRADES working with or without the push (`Note: GRADES reply`). This follows the submitted template but is not in the spec on `main` (see 16).
- Definition of an approved, linked parent (`Note: parent`): linked their own WhatsApp to the child by KlassApp number or by form, and the link approved by the school admin **or class teacher**. The spec says "approved, linked parents" without defining who approves.
- Toshi answers "staff, parents and students … within their role" (`Note: Toshi`).
- A wiring quirk: `Parent: replies GRADES or taps See results, receives the marks` is fed both by `System: record sitting released` and by the WhatsApp send. With the switch on, it would run twice. `System: collect release outcomes` is a blueprint-only join node.
- `Demo: sample school` encodes the rule "null = blank, a real 0 is a real mark", which matches the spec's "missing marks stay blank. They are not zero".

## In the spec, not in the blueprint

- `ReleaseGate` and `ResultsService` as the shared engines, and "one service owns each behaviour". The blueprint labels only the assignment service.
- Release past sittings (2), staff report-card preview (4), class-teacher and admin entry and Toshi entry (5), lock and reopen (14), the file parse and preview (15).
- Report cards: mid-term and end-of-term rows from `ResultsService`, school exam-type settings, and division thresholds and ranking left as they are (step 7, Decision 10, phase 5).
- Alumni and student portals, inbound MARKS and REPORT (12, 13).
- The 24-hour window rule and the separate template needed for a pushed report-card PDF.
- The phase 4 clean-ups (the broken filter action, the PDF's old year lookup, the second total) and retiring `exams.status` and `Exam::changeExamStatus`.

## Agreements worth noting

Saving, submitting, approving and releasing are separate states (Draft → Submitted → Returned/Approved → Released). Approval is required with no override. By default only the admin releases, and the class-teacher setting is off by default. Submitting does not notify parents. The return reason is visible only to the owning teacher and school admins, and it shows on the entry page. The entry form shows saved marks. The sheet covers one sitting for the whole class, with a stream column and filter, and blanks are never zero. Nothing reaches a family before release. Google Sheets, a whole-term sheet and a release override are out of this version. Each query is scoped to one school.
