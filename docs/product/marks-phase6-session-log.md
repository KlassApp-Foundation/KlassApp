# Marks Phase 6 session log (for knowledge.md)

*This entry belongs at the end of `knowledge.md`. It is here because the workspace
write guard refuses any write to `knowledge.md` (its own line 32 contains an
`Authorization` header example that reads as a credential assignment). Append it
as-is, or move it, once the guard has an allowlist entry for that line.*

## Session: 2026-10-26 — Marks Phase 6 release rules (draft #1244)

**Work:** Built Phase 6 on `cursor/marks-phase-6-release`, stacked on #1243, as draft [#1244](https://github.com/KlassApp-Foundation/KlassApp/pull/1244). **6a** removed the approval override from the service, the controller validation and the release screen: `requireApproval()` takes no reason and `create()` no longer accepts one. **6b** added `teachers_may_release_marks` in `school_details`, off by default and fail-safe, cached per school and forgotten on write, with a toggle on Maintenance settings; `links()` is now the single gate, so a class teacher has no releasable class until the school turns it on and never reaches another class or the whole school. **6c** added `sittingIsPast()` and `requirePastConfirmation()` plus a `confirm_past` checkbox that renders only for a sitting in an earlier term or year. **6d** added `marks_releases.whatsapp_send` (false by default, on the release screen and the release page), `App\Services\WhatsApp\TemplateGate` over `config/whatsapp.php` (the approved list is empty unless a deployment records it; nothing calls Meta), and recipient resolution to approved, linked, opted-in parents of the students in the released class and sitting, enforced in both `deliver()` and `deliverOne()`. Demo follow-along sends are unchanged (a demo send is not a school sending a template, decision 3 in `marks-flow.md`), and the demo-only recipient guard still runs first for a real school. **6g** recorded the build in `docs/product/marks-flow.md` under "Phase 6 as built".

**Blocked by the write guard:** **6e** (one line per child in the empty MARKS reply) is not in the branch. The guard refuses `app/Http/Controllers/Api/WhatsAppController.php` on its own docblock at line 510 (`...hub.verify_token=...` reads as a credential assignment), and the owner asked for blocked files to be left for the morning. The test is written and red in `tests/Feature/Marks/MarksPhase6MarksReplyTest.php`; the exact edits are listed in #1244. Also blocked: `tests/Feature/Demo/MarksReleaseTest.php` (three assertions still state the pre-Phase-6 rule), `config/services.php` (worked around with `config/whatsapp.php`) and this file.

**Guard exception:** with the owner's approval, three files were patched by script because the guard flags pre-existing lines: `MarksReleaseService.php` (its `$secret = (string) config(...)` line, left untouched), `ReleaseRequiresApprovalTest.php` (fake `bcrypt('x')` fixture) and `MarksPhase3SubmissionTest.php` (`VerifyCsrfToken::class`). Full diffs are in #1244. An allowlist entry for those lines is wanted.

**Tests:** five new files under `tests/Feature/Marks`: Approval 3, WhoMayRelease 5, PastSitting 5, WhatsAppGate 5, MarksReply 2. `tests/Feature/Marks` is 60 tests green. The two demo release suites are 30 tests with 3 assertions now describing the old rule (blocked file).

**Status:** draft, not merged, nothing deployed, no production access, WhatsApp exercised only through fakes. 6a–6d green, 6e blocked, 6f green for five of six rules, 6g's doc update in, the PR deliberately not marked ready.
