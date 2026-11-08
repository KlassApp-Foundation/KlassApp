# Parent and child linking

Design only. No code changes. Serves **T-095** (parents recorded against one or more students; a WhatsApp link button on the parent's phone) and **T-096** (automatic linking for parents who share a phone).

> **Status.** Complete as a design. Added in a second pass: the trace of every caller of `ParentLinkService::linkByStudentId()`, wireframes for the admin screens (described in text) and the WhatsApp message copy. Still not done: any code, and answers to the open questions at the end.

References are to `main` at `01d87832`.

## What exists today

- **Data.** A parent is a `users` row in group 7. `student_parent_links` (`app/Models/StudentParentLink.php`: `school_id`, `parent_id`, `student_id`, `status`) joins a parent to each child, so one parent can already have several children and one child several parents. `WhatsAppUser` holds the phone and the parent's `user_id`.
- **WhatsApp linking requests.** A parent who messages the school's number is asked for their child. `ParentLinkRequestService` records a `ParentLinkRequest` (`app/Models/ParentLinkRequest.php`: phone, parent and WhatsApp profile name, the child name and class they typed, a suggested and a matched student, `candidate_student_ids`, `status`, `source`). It has three entry paths: a Flow form submission, a typed KlassApp number (KLS number, `createFromKlassAppNumber`) and a School Pay code. A new request is **pending**; an admin approves or rejects it on the link request board (`LinkRequestBoard`) or through the approvals inbox. Pending, approved and rejected messages are sent back to the parent (`pendingStatusMessage`, `approvedStatusMessage`, `rejectedStatusMessage`).
- **The link itself.** `ParentLinkService::linkByStudentId()` (`app/Services/ParentLinkService.php:20`) creates or reuses the parent for a phone and adds the child. It handles "already linked", "additional child for the same phone", "phone previously held by a non-parent" and "new parent". It does **not** itself check that the person asking is entitled to the child; the check is the admin approval that happens before it is called from `ApprovalDecisionService` (`app/Services/Approvals/ApprovalDecisionService.php:203`).
- **Cross-school.** The service notes one phone maps to one parent user with `school_id` null and many links across schools.
- **Admin side.** `ParentLinkStudentPicker` (a Livewire component) lets an admin link a parent to a student by hand; the parent profile page lists linked children and WhatsApp status ("Linked" or "Not linked").

So the pieces for "many children per parent" and "a parent confirms by WhatsApp, an admin approves" are there. What is missing is (a) a button on the parent's phone, (b) a way to link a second parent who shares a phone without an admin approving each one, and (c) a clear record of what the parent had to prove.

## Proposal

### 1. Parents recorded against one or more students (T-095, data entry)

- On the parent profile and on adding a parent, the student field becomes a **multi-select of children** (search by name or KLS number), so a parent with three children is recorded once, not three times. It writes `student_parent_links` rows through `ParentLinkService` (one implementation), never directly.
- Entering a parent whose phone already belongs to a parent in the school **offers that parent** ("Grace Nakato already has this number. Add the child to her?") instead of creating a second parent row.

### 2. A WhatsApp link button on the parent's phone (T-095)

- In the parent's profile in the web portal on a phone, and in the message the school sends when a parent is added, show **Link on WhatsApp**: a `wa.me` link to the school's WhatsApp number with a prefilled first message that carries a **one-time link code** ("LINK 7K3Q-9XPA").
- The code is created per parent and child, expires (suggested: 7 days), works once, and is stored hashed. The parent's WhatsApp message arrives with their phone number from Meta, so the system knows which number is asking.
- When the message arrives, the number is checked against the number recorded for that parent (rule below). If it matches, the link is made with no admin step, because the school has already recorded this parent and child; if it does not match, it goes to the normal approval board with the code noted on the request.

### 3. Parents sharing a phone link automatically once they confirm (T-096)

The common case: two parents, one phone, one or more children.

- The second parent messages from the same number. Today the second child attaches to the first parent (`linked_additional_child`), which is right for siblings, but a different parent on the same phone becomes invisible.
- The proposal: when a number is already linked to a parent and a new link request names a **different person** (a different parent name than the first), ask them to confirm **either** the matching details the school holds (the child's full name and date of birth, or the guardian name and relation on the child's record) **or** the child's KlassApp ID (KLS number). A correct answer links them as an additional parent on the same WhatsApp identity, with their own `student_parent_links` row, no admin step. An incorrect or partial answer falls to the approval board.
- "Same phone" is not a person. The design keeps parents as separate `users` rows even when they share a `WhatsAppUser` row, so reports and messages can name the right person.

### What stops a wrong parent from linking

1. **School-recorded numbers first.** Automatic linking only happens when the number or the details the parent gives match what the school **already recorded** for that child (a guardian phone on the student, a parent profile, the admission record). A number the school has never seen goes to a human.
2. **Two factors for automatic linking:** the number (from Meta, not typed) **and** one of: the one-time code, the child's KLS number, or two matching details (full name and date of birth). A name alone is never enough, a class alone is never enough.
3. **Candidate lists never name other children.** The message must not say which children exist or hint at wrong answers. Replies to a wrong answer are the same for "no such child" and "wrong detail".
4. **Rate limits.** Per number and per child: a small number of attempts per hour, then a lock and an admin notice. A lock never reveals whether the child exists.
5. **Never cross school silently.** Matching is inside the school named by the code or the KLS number's school. The KLS number's school is resolved from the student record (scoped by id), not from what the parent types.
6. **Every automatic link is notified.** The first parent and the school admin see "A second parent was linked to Amara on WhatsApp" and can unlink in one click (the existing `LinkRequestBoard::unlink`).
7. **Audit.** Each link records how it was made (admin, code, KLS number, details) and the evidence that matched, never the raw answers.
8. **Nothing unreleased is reachable by linking.** Linking gives a parent the same view as any parent; marks still come only through the release gate.

### How it fits the existing flows

| Flow | Change |
| --- | --- |
| Flow form and typed KLS number | Unchanged for a first link; a second parent on a known number follows rule 3 |
| School Pay code | Unchanged |
| Link request board and approvals inbox | Still the fallback for anything that cannot be matched; shows how much matched and what did not |
| Admin picker (`ParentLinkStudentPicker`) | Becomes multi-child; calls the same service |
| `ParentLinkService` | Gains the confirm step and a link method that records its evidence; one place for every path |

### Tests (when it is built)

- Code: used once, expires, hashed, wrong number goes to approval.
- Second parent: correct KLS number links; wrong answer links nothing and says the same as "not found"; a name alone never links.
- Rate limit: locks after the limit without saying whether the child exists.
- School scope: a code or KLS number from another school never links; every query scoped by `school_id`.
- Unlink by the first parent's admin removes only that parent's link.

## Trace of every caller of `ParentLinkService::linkByStudentId()`

`linkByStudentId()` is the single place that creates the parent and the link. It does not check entitlement; whoever calls it must. Callers on `main` at `01d87832`:

| Caller | Reached by | Entitlement check before the call |
| --- | --- | --- |
| `ApprovalDecisionService.php:203` | An admin approving a `ParentLinkRequest` | The admin's decision. This is the intended path. |
| `WhatsAppController::linkParentToStudent()` (`Api/WhatsAppController.php:1154`), called from **line 969** | The in-flight "Yes, link me" button (`link_{studentId}`) | Not routed through the approval board. |
| `WhatsAppController::linkParentToStudent()`, called from **line 1074** | A parent typing the school's own student ID or board registration number | Not routed through the approval board. |
| `SetupUiReviewDemoSchool.php:232` | A demo setup command | Runs only for the demo school; synthetic data. |

Separately, `processCodeVerificationForMeta()` (the School Pay code path, around line 1208) writes the `WhatsAppUser` row and links the number directly when the student has no parent yet, also without the approval board.

So today the KlassApp number path (`requestKlassAppNumberLink`) and the Request Link form go through approval, but three other paths can link without an admin. The rules in "What stops a wrong parent from linking" are written for the approved design; these direct paths do not follow them (no second factor, no rate limit, no notification).

**Proposal for the callers:** every WhatsApp path files a `ParentLinkRequest` through `ParentLinkRequestService` and ends in the one confirm step above. `linkParentToStudent()` stops calling `linkByStudentId()` itself. Button payloads carry a signed, expiring reference, never a bare record id. A test per path (rule 40) proves that none of them links without either an admin approval or the two-factor confirm.

## Admin screens (wireframes, described)

Built from the admin design system (`design/system/concepts/admin-mvp`), not the legacy dashboard kits.

**A. Parent profile, "Children" card** (existing parent page, replaces the single student field)

```
Children                                              [ + Add child ]
------------------------------------------------------------------
 (avatar) Amara Example      Primary Three East      KLS0000123   [Unlink]
 (avatar) Tendo Example      Baby Class              KLS0000456   [Unlink]
------------------------------------------------------------------
WhatsApp   ● Linked  +256 7xx xxx 456        [ Send link on WhatsApp ]
           Last message 3 days ago
```
- "+ Add child" opens a multi-select search (name or KLS number), scoped to the school, with checkboxes. Save writes through `ParentLinkService`.
- "Send link on WhatsApp" is visible only when the parent has a phone and is "Not linked". It sends the template below and shows "Sent, expires in 7 days". When Linked it is replaced by the linked phone.
- Empty state: "No children linked yet. Add a child so this parent can see fees and results."

**B. Add parent (modal or page)**
```
Parent name  [______________]     Phone  [+256 ___ ______]
Children     [ search name or KLS number            v ]  (chips: Amara x  Tendo x)
[ ] Send the WhatsApp link now
                                     [ Cancel ]  [ Save parent ]
```
- Typing a phone that already belongs to a parent in this school shows an inline banner under the field: "Grace Example already has this number. [ Add the children to Grace ] [ This is a different person ]". Choosing the second makes a second parent on the same phone (the T-096 case).

**C. Link request board** (existing `LinkRequestBoard`, three additions)
```
Request from +256 7xx xxx 456   "Grace E."   asked for: Amara (Primary Three)
 Matched: phone on file ✓   child's KLS number ✓   date of birth ✗ (not given)
 How: typed KLS number                       Code: none
 [ Approve ]  [ Reject ]  [ Ask the parent a question ]
```
- A "Matched" row lists what matched and what did not, so an admin decides on evidence.
- Automatically linked second parents appear in a "Linked automatically (last 7 days)" list with `[Unlink]` beside each (existing `unlink`).
- Empty state: "Nothing waiting. New requests from WhatsApp appear here."

**D. Settings, "Parent linking"** (new, small): code expiry (default 7 days), whether automatic linking of a second parent is allowed for this school (default **off**, consistent with the opt-in rule for new behaviour), and who is notified.

All lists scoped by `school_id`; every row addressed by id.

## WhatsApp message copy (drafts, for Rasta to edit)

Variables in `{braces}`. Template messages (outside the 24-hour window) must be approved by Meta before use; the text below is the proposed content. Keep each message short, plain, no child names in the first message to an unverified number.

1. **Invite to link** (template, sent when a parent is added)
   > Hello {parent_first_name}. {school_name} uses KlassApp to share fees, results and attendance. Tap the button to connect this number. The link works for 7 days.
   >
   > [ Connect on WhatsApp ]

   The button opens a chat with a prefilled `LINK {code}`. No child name appears here.

2. **Linked, first parent**
   > You are connected to {school_name}. You can now ask for fees, results and attendance for {child_first_name}.
   > [ Fee balance ] [ Exam results ] [ Attendance ]

3. **Linked, second parent on the same phone** (to the person who just linked)
   > You are connected as an additional parent for {child_first_name}. Send *HELP* to see what you can ask for.

4. **Ask for proof, second parent** (when a different person messages from a known number)
   > This number is already used for a parent at {school_name}. To add you as another parent, please send your child's KlassApp ID (it starts with KLS). If you do not have it, tap *Request Link* and the school will review.
   > [ Request Link ]

5. **Wrong or unknown answer** (identical for "no such child" and "wrong detail")
   > We could not match that. Check the ID on your child's report card and try again, or tap *Request Link* and the school will review.

6. **Rate limit reached** (never says whether the child exists)
   > Too many tries for now. Please try again tomorrow or contact the school office.

7. **Notice to the first parent / school admin** (only if the school switched this on; see open question 5)
   > Another parent has been connected to {child_first_name} at {school_name}. If this is not expected, contact the school office.

8. **Pending, rejected, approved** keep the existing `ParentLinkRequestService` messages.

Copy rules: no mention of other children, no candidate lists, no marks, no fee amounts before linking, and nothing that changes the answer between "not found" and "wrong detail".

## Open questions for Rasta

1. **What counts as proof?** Is the child's KLS number enough on its own (a parent may see it on a fee receipt), or must it come with a second detail?
2. **Do schools record guardian phones today?** Automatic linking is strongest where the school has the parent's number on file. How many schools have it?
3. **Who decides "a different person"?** Compare the WhatsApp profile name, a name the parent types, or ask outright "Are you the same person as ...?"
4. **Second parent's access.** Same view as the first parent, or limited until an admin confirms?
5. **Notifications.** Is telling the first parent "a second parent was linked" acceptable (it can be sensitive in separated families)?
6. **Expiry of codes** and where the link button is shown (parent portal, SMS or WhatsApp invite, printed letter).
7. **Cross-school parents** (one phone, children at two schools): does the code or KLS number linking behave the same, and does the first school see the second?
8. **The direct paths found in the trace** (next section) skip the approval board. Should they be closed before anything in this document is built? Details have been passed to Rasta privately, not recorded here (public repository).
