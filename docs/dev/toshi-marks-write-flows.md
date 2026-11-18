# Toshi marks write flows

Design only. None of these flows are built here. A future tool must call the methods named below and must not calculate a mark, a total, an aggregate, or a division.

Toshi's confirmation step is `ConfirmsBeforeWrite`, which calls `ToshiActionService::confirmationPayload()`. The panel stores that payload and, only after the person confirms, `AgentToshi::executeConfirmedTool()` sets `ToshiActionService::$bypassConfirm` and calls the tool again. The tool name has to be in `AgentToshi::TOOL_CLASS_MAP`. There is no confirmation-gated send for a reminder to a class teacher. A draft stays a draft.

## Release marks

Call `MarksReleaseService::authorise()`, then `requireApproval()`, then `requirePastConfirmation()`, then `create()`, then `run()` when the release is for now. `runDue()` is the scheduled path and calls `run()`.

The confirmation step already on the screen is `MarksReleaseController::preview()`, then `storeRelease()`. A pilot list is not released on the first post: `storeRelease()` returns the preview until `pilot_confirmed` is set. A Toshi tool would stop at `ConfirmsBeforeWrite` before `create()`.

Scope: `links()` and `isSchoolAdmin()`. `authorise()` loads the exam with the user's `school_id`. A class release requires the stream to be in `links()`. Another class is refused. Another school is not found. A whole-school release requires `isSchoolAdmin()` (school admin or head teacher of that school).

Release gate: `create()` stores `status` `scheduled`. Parents are not told at that point. `run()` moves the row to `released` inside its transaction, after `requireApproval()` has already refused any subject that is not approved. `EloquentReleaseGate::isReleased()` and `isTermReportReleased()` read that released row. Nothing in this flow should call `WhatsAppBusinessService` before `run()` has saved `released`.

## Approve a set

Call `MarksSubmissionService::approve()` for one subject, or `approveSelected()` for the ticked rows. Both write the approval. The class comment on `MarksSubmissionService` says parents are not messaged.

The screen posts straight to `ExamMarksSubmissionController::approve()` and `approveSelected()`. A Toshi tool would stop at `ConfirmsBeforeWrite` before either method. Approving is not a release and does not call `MarksReleaseService::run()` or `deliverOne()`.

Scope: `reviewScope()` allows school-admin groups, or the teacher who heads that exam's class (`ExamAuthorization::isClassTeacherForExam()`). `assertSameSchool()` refuses another school. A class teacher who does not head the class is refused. `approveSelected()` also refuses anyone who is not in the school-admin groups before it writes.

Release gate: an approval does not change `marks_releases` and does not satisfy `EloquentReleaseGate`. Families still cannot see the sitting until a release exists.

## WhatsApp to linked parents

Call `MarksReleaseService::run()` only. It queues `SendMarksReleaseMessage` after the release row is saved as `released`. The job calls `deliverOne()`. Do not call `WhatsAppBusinessService::sendText()` or `sendTemplate()` from a Toshi tool.

`deliverOne()` sends one parent, and only when the release is already released. It skips the send when WhatsApp is switched off, when `realSendBlocker()` returns a reason (`TemplateGate::blocker()` for `TemplateGate::RESULTS_RELEASED` on a real school), or when the demo-only guard is on for a school that is not a demo. It re-reads the parent row and skips a link that is not approved or a parent who opted out. A pilot release skips anyone not on `pilot_recipients`.

The recipient list is built in `deliver()` from approved parent links in the same school (`StudentParentLink` status active) who are opted in. A learner with no linked parent is skipped. `audienceSummary()` and `preview()` are display only and send nothing.

Confirmation sits on the release preview, before `create()` / `run()`. There is no second confirm inside `deliverOne()`.

## n8n signed marks-released webhook

Issue #1341 describes a school webhook that is not sent by this application yet. It is separate from the demo webhook.

The demo path is `MarksReleaseService::queueDemoWebhook()`, the job `SendDemoMarksReleaseWebhook` (one try), and `sendDemoWebhook()` / `postWebhook()`. `run()` registers that job in `DB::afterCommit` only after the release is saved as `released`, and only when the demo counts are present. `postWebhook()` signs the raw JSON body with `X-KlassApp-Signature` and refuses production, a school that is not a demo, a flag that is off, and a URL that is not https.

The school webhook in #1341 would be a different sender: counts and ids only, the same signature header over the raw body, five tries, and a log line that does not include the body or the secret. The place to queue it is the same `afterCommit` inside `run()`, after status `released`. It must not be called from `deliverOne()`, and it must not include a learner's marks. n8n checks the signature and does not send WhatsApp. A school does nothing until a later change starts sending it.
