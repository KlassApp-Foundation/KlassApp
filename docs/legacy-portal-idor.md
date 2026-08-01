# Legacy portal IDOR (tracking)

**Branch:** `fix/legacy-portal-idor-v2` (Part A)  
**Status:** Part A in progress — `studentassignment` / `studentHomework` ownership Gates  
**Independence:** Not blocked by Toshi / platform / teacher role PRs

## HIGH backlog item

Application-level IDOR on legacy Gates for:

- `studentassignment` ← **Part A**
- `studentHomework` ← **Part A**
- `event` (later)
- `post` (later)

These Gates previously authorized with **school_id-only** checks, which affected **show** / **destroy** paths: any authenticated user in the same school could access or delete another user’s records without ownership checks.

## Planned fix

Add ownership (and role-appropriate) policies on show/destroy for the affected resources, replacing school_id-only Gate checks.

### Part A (this branch)

- Gates require `(int) user.id === (int) resource.user_id` **and** school_id match
- Portal + API student assignment/homework show & destroy enforce ownership
- API ignores forged route `{student_id}` and binds to `Auth::id()`
- Teacher/admin paths do **not** use these Gates (verified by grep)
