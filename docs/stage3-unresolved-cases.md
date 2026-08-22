# Stage 3 — Unresolved Cases (needs school confirmation)

Date: 2026-08-15
Scope: school 104, P7 exams 42/43/44 (June/July/EOT)
Prepared for: school administration confirmation before any further data moves.

These 5 cases were deliberately **NOT** migrated in the Stage-3 run. Each requires
human confirmation of the correct target account — no automated string-similarity
match was used because the cost of a wrong move is data on the wrong child.

---

## Case 1 — NAKITENDE LEILA (stub user 3648)

- Stub account: `u3648 NAKITENDE LEILA` (active, link 82, section 45) — holds 12 marks (exams 42/43/44, subjects 284-287)
- Best-guess twin: `u3293 LEILAH NALWANDE` (active, link 81, section 51) — currently 0 marks
- Why not auto-moved: surname differs (NAKITENDE vs NALWANDE) and only "LEILA/LEILAH" matches. Two possible spellings of the same child — but could be two different children.
- Ask the school: does P7 have "LEILAH NALWANDE" or "LEILA NAKITENDE"? If they are the same child, approve moving u3648 → u3293.

## Case 2 — NYANGOMA PRECIOUS (stub user 3653)

- Stub account: `u3653 NYANGOMA PRECIOUS` (active, link 82, section 45) — holds 12 marks
- Best-guess twin: `u3298 PRECIOUS ARRNATIVE NUWOMA` (active, link 88, section 51) — currently 0 marks
- Why not auto-moved: only the given name "PRECIOUS" matches; surnames (NYANGOMA vs ARRNATIVE NUWOMA) diverge.
- Ask the school: confirm whether PRECIOUS ARRNATIVE NUWOMA is the same child as NYANGOMA PRECIOUS.

## Case 3 — RUTAGIRWA ERICKSON (stub user 3666)

- Stub account: `u3666 RUTAGIRWA ERICKSON` (active, link 82, section 45) — holds 4 marks (exam 42 only)
- No active twin found anywhere in school-104 users. Only candidate is inactive slug `u3385 erickson33852` (link 81), which carries no marks and looks like a placeholder.
- Ask the school: does P7 have an ERICKSON (RUTAGIRWA / any surname)? If yes, provide the full name / existing account so marks (exam 42) can be placed on the right child. If the child left before onboarding, decide whether the 4 marks stay on the stub or should be removed.

## Case 4 — JORDAN NIWAGABA (user 3380, orphan)

- Status: **inactive**, link 81, but holds 12 real marks (exams 42/43/44) on section 45.
- The import matched an exact-name inactive account instead of an active student. Active P7 candidates with similar names: `u3314 JORDAN MUGABE` (link 88, 0 marks), plus other JORDANs in other classes (JORDAN ATUKUNDA u2661, JORDAN AMANYA u2813, JORDAN NAMUMPEIRE u3154, JORDAN ANTWUJUKA u3173, SEAN JORDAN AREMWA u3061).
- Ask the school: is JORDAN NIWAGABA the same child as JORDAN MUGABE? Which account is canonical?

## Case 5 — DANELLA NIWAHA (user 3381, orphan)

- Status: **inactive**, link 81, holds 12 real marks (exams 42/43/44) on section 45.
- Active P7 candidates: `u3297 DANIELLA NUWAHA` (link 88, 0 marks) — spelling differs (DANELLA vs DANIELLA, NIWAHA vs NUWAHA).
- Ask the school: is DANELLA NIWAHA the same child as DANIELLA NUWAHA? Which account is canonical?

---

## Post-migration state (executed portion)

- 300 marks re-sectioned 45→51 for 29 real P7B students (verified: 0 sec45 rows remain for them, >0 sec51 each)
- 200 marks moved stub→twin across 19 pairings (verified: all stubs empty, all twins at expected 8/12/4 cells, all on section 51)
- 19 stub users deactivated + their academics rows soft-deleted (verified)
- P1 section-45 marks outside exams 42-44 untouched: 1350 rows before and after
- No dangling mark rows; no duplicates created

Backup: `/root/backups/stage3_20260815_222618.sql` (7532 marks / 3247 users / 3136 academics — row counts verified against live pre-migration DB).