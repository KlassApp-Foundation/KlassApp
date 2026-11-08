# Parent and child linking

Design only. No code changes. Serves **T-095** (parents recorded against one or more students; a WhatsApp link button on the parent's phone) and **T-096** (automatic linking for parents who share a phone).

> **Where I stopped.** This is a first pass written at the end of a one-hour session. It maps the existing linking code and proposes the three changes with the safety rules. Not done: wireframes for the admin screens, a trace of every caller of `ParentLinkService::linkByStudentId()` (one direct caller in `app/Http/Controllers/Api/WhatsAppController.php:1154` was not traced), and the message copy for the WhatsApp templates. Those are marked below.

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

## Open questions for Rasta

1. **What counts as proof?** Is the child's KLS number enough on its own (a parent may see it on a fee receipt), or must it come with a second detail?
2. **Do schools record guardian phones today?** Automatic linking is strongest where the school has the parent's number on file. How many schools have it?
3. **Who decides "a different person"?** Compare the WhatsApp profile name, a name the parent types, or ask outright "Are you the same person as ...?"
4. **Second parent's access.** Same view as the first parent, or limited until an admin confirms?
5. **Notifications.** Is telling the first parent "a second parent was linked" acceptable (it can be sensitive in separated families)?
6. **Expiry of codes** and where the link button is shown (parent portal, SMS or WhatsApp invite, printed letter).
7. **Cross-school parents** (one phone, children at two schools): does the code or KLS number linking behave the same, and does the first school see the second?
8. **The direct call at `WhatsAppController.php:1154`.** Which path is it, and does it bypass approval? Not traced in this pass.
