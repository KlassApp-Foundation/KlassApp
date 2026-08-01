# Legacy portal IDOR (tracking)

**Branch:** `fix/legacy-portal-idor`  
**Status:** Backlog — scheduled after role rollout  
**Independence:** Not blocked by Toshi / platform / teacher role PRs

## HIGH backlog item

Application-level IDOR on legacy Gates for:

- `studentassignment`
- `studentHomework`
- `event`
- `post`

These Gates authorize with **school_id-only** checks, which affects **show** / **destroy** paths: any authenticated user in the same school can access or delete another user’s records without ownership or role-scoped checks.

## Planned fix

Add ownership (and role-appropriate) policies on show/destroy for the affected resources, replacing school_id-only Gate checks. Implementation work lands on this branch after the role rollout sequence; this commit only tracks the item.
