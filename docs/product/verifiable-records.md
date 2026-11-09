# Verifiable records: term records that can be proven later

*Design doc. Nothing here is built. It is a proposal for what a released term record is, how it is proved, and where it attaches to the release code that already exists.*

**Problem.** A report card is a PDF a school can reprint. A parent, a receiving school or an employer has no way to tell whether the marks on it are the ones the school released, or whether they were changed afterwards. KlassApp already releases marks per sitting (#1244) and writes a per-family result (`marks_releases.result`), but a release is a sending event, not a record.

**Goal.** Every released term record is immutable, versioned, hashable and verifiable by anyone holding the report card, without a KlassApp account and without exposing any other learner.

**Non-goals.** No learner data on a public chain. No replacement of the school's own certificate. No claim that the marks are *correct* — only that they are the ones released, by that school, at that time.

---

## Step 1 — A release writes an immutable snapshot

### What a snapshot is

One row per learner per term (not per sitting), holding the record as the family sees it: subjects, marks, grades, totals, average, position, division, attendance, remarks, the school's grading scale identity, and the report definition used. A term can contain several sittings; the snapshot is the term view.

```
record_snapshots
  id
  school_id            -- tenant scope, as everywhere
  student_id
  academic_year_id
  academic_term_id
  version              -- 1, 2, 3 ... per learner per term
  supersedes_id        -- the snapshot this one replaces; null for version 1
  reason               -- required when version > 1
  payload              -- canonical JSON, see step 2
  payload_hash         -- sha-256 of the canonical bytes
  prev_hash            -- the learner's previous term snapshot hash (chain, step 2)
  released_at
  released_by
  state                -- 'current' | 'superseded' | 'revoked'
  created_at
  UNIQUE (school_id, student_id, academic_year_id, academic_term_id, version)
```

`state` is the only mutable column. A snapshot is never updated, never deleted; a correction inserts a new row and flips the old one to `superseded`. `revoked` exists for a record that must not be relied on (for example a release made in error); it keeps the row and marks it, rather than hiding it.

### Where it hooks into the release code

`MarksReleaseService::run()` (`app/Services/MarksRelease/MarksReleaseService.php:607`) already:

1. claims the release (`scheduled` → `sending`),
2. computes the per-family result,
3. writes `status = released`, `released_at`, `result` (`:622`–`:624`),
4. dispatches the send jobs.

The snapshot step goes **between 3 and 4**: after the release row is released, before any family is messaged. A release never messages a family whose snapshot was not written.

```
run(releaseId)
  claim
  deliver()            -- decides who is messaged
  release->released     -- existing
  snapshots->capture(release)   -- NEW: one row per learner in scope, version 1 or N
  dispatch jobs          -- existing
```

If `capture()` fails, the release is not messaged and the release row is marked `failed`, not `released`. Partial capture is worse than no capture: a term with 40 of 300 learners snapshotted cannot be verified as a whole.

### What changes in Phase 6

Phase 6 (#1244) ends with a released release, a result payload and a WhatsApp gate. To add step 1 without rewriting it:

| Change | Where | Risk |
|---|---|---|
| New table + model | `record_snapshots` migration, `App\Models\Records\RecordSnapshot` | Additive |
| Capture service | `App\Services\Records\SnapshotService::capture(MarksRelease $release)` | New code only |
| One call | `MarksReleaseService::run()` after the release is saved, before dispatch | Small, testable |
| Release status | add `failed` to `marks_releases.status` and to the show page | Existing rows unaffected |
| Corrections | a new admin action "Correct a released term", which opens a version with a reason; it does not edit marks | New screen, later phase |
| Tests | release writes one snapshot per learner in scope; a school-scope release writes every learner it actually messaged or would message; a second release of the same term supersedes, never overwrites | New test file |

`marks_releases.result` keeps its current job (what was sent to whom). The snapshot is the record; the result is the delivery log. They must not be merged.

### Scope rule

Snapshots follow what was released, not the whole school: a class release snapshots that class's learners; a school release snapshots every learner with marks in the sitting. A learner with no marks is not snapshotted — an empty record is not a record.

---

## Step 2 — Canonical serialisation and a hash chain

### Canonical bytes

`payload` is serialised by one function, not by `json_encode` at the call site:

- keys sorted byte-wise, UTF-8, no whitespace;
- numbers rendered as decimal strings with a fixed scale (marks `78.5`, never `78.50` or `78.50000001`), because float formatting differs across PHP builds;
- dates ISO-8601 with the school's timezone offset, and the UTC instant alongside;
- no nulls: an absent field is omitted, never serialised as `null`, so "no position" and "position zero" cannot look alike;
- arrays keep their order only where the order is meaning (subject order as printed); otherwise sorted by a stable id.

The canonicalisation function is versioned (`canon_version = 1`) and stored on the row, so a later change to serialisation never invalidates an old hash.

### The chain

Each snapshot stores `prev_hash` = the `payload_hash` of the learner's previous term snapshot in the same school and year. That makes term 2 depend on term 1: altering term 1 breaks every later term's link, and a deleted term is a visible gap.

```
snapshot(term 1)  hash H1   prev = null
snapshot(term 2)  hash H2   prev = H1
snapshot(term 3)  hash H3   prev = H2
```

The chain is per learner, not per school, so one family's record never reveals another family's hash. A school-level manifest (a hash of all learner roots for a term) is a later option, not step 2.

---

## Step 3 — Signing

A hash alone proves nothing: anyone can compute one. The signature is what ties a record to KlassApp.

**What is signed.** A small envelope, not the payload again:

```
{
  "v": 1,
  "snapshot_id": 12345,
  "school_id": 7,
  "student_ref": "<opaque id, not a name>",
  "term": "2026-T2",
  "payload_hash": "<sha-256>",
  "prev_hash": "<sha-256 or null>",
  "issued_at": "2026-07-14T09:12:04+03:00"
}
```

**Algorithm.** Ed25519 (`sodium_crypto_sign_detached`), signature stored base64 in `record_signatures` with `key_id`, `algorithm`, `signed_at`. Ed25519 because it is short, deterministic and available in PHP's sodium extension on every host we run.

**Keys.**
- One signing key per environment, held in the secret store (Doppler), never in the repo, never in the database.
- `key_id` on every signature, and a `signing_keys` table with public key, `valid_from`, `valid_to`, `state` (`active`, `retired`, `compromised`). Public keys are safe to publish; that is what makes verification possible without us.
- Rotation: a new key becomes `active`; the old one stays `retired` and keeps verifying old records. Rotating never re-signs history.
- Compromise: the key is marked `compromised`, its window is published, and records signed in that window are flagged on the verification page rather than silently trusted.

**School co-signing (later).** The envelope is designed for a second signature: `record_signatures` is a list, not a column. A school that wants to co-sign adds its own key and signature. Nothing in the format changes.

---

## Step 4 — Public verification

**The page.** `GET /verify/{token}` — a token, not a student id, so the URL cannot be walked. The token is random (128 bits), stored on the snapshot, and printed in the QR code on the report card. It identifies the record, not the person.

What it shows:

- the school's name and the term;
- that the record is authentic, superseded, or revoked — with the date;
- the fingerprint (first 16 hex characters of the payload hash) and the signature's key id;
- the learner's name **only** in the form the school printed on the card, and only if the token holder already has the card;
- a one-line statement of what is being asserted: "these are the results Sample School released for this learner for Term II 2026".

What it never shows: other learners, other terms, fees, contacts, addresses, or a browsable list. No search by name. Rate-limited, no indexing (`noindex`), and the token is revocable per record.

**The QR code.** Generated per report card, encoding only the verification URL. It must survive print at 20 mm, so: no logo in the middle, error correction level M, and a short domain.

**Offline fallback.** The report card prints the fingerprint and the verification domain, so a paper card can still be checked later if the QR is damaged.

---

## Step 5 — Optional later: anchoring hashes

If schools ever need proof that a record existed at a time without trusting KlassApp's database, anchor **only** hashes: a Merkle root of a term's snapshot hashes, published once per term to a public ledger or a public timestamping service. Never names, never marks, never learner ids. This is deliberately last: it adds operational cost and a public artefact, and nothing in steps 1–4 needs it.

---

## Threat model, in plain terms

| What could go wrong | What stops it |
|---|---|
| A school edits a released mark | Snapshots are append-only; the correction is a new version with a reason and a visible history |
| KlassApp's database is edited by hand | The hash chain breaks at the edited row, and the signature no longer matches |
| Someone prints a fake report card | The token does not resolve, or resolves to a different fingerprint |
| A token leaks | It reveals one record's existence and its own content only; revocable; no search |
| KlassApp's signing key leaks | Key id and compromise window published; affected records flagged; rotation does not re-sign history |
| A school disputes what was released | The snapshot, its hash, its signature and the release row's delivery log are separate evidence |

**Failure modes to design for.** Capture fails mid-release (release fails, nothing sent); a term is released twice (version 2, reason required); a learner moves school (chain stays with the school that released); a term has no marks (no snapshot, and the release says so).

---

## Build order

| Step | Scope | Depends on |
|---|---|---|
| 1 | Snapshots on release, append-only, corrections as versions | Phase 6 merged (#1244) |
| 2 | Canonical serialisation + per-learner hash chain | 1 |
| 3 | Ed25519 signing, key table, rotation runbook | 2 |
| 4 | Public verification page + QR on both report templates | 3 |
| 5 | Hash anchoring | 4, and only if a school asks |

Steps 1 and 2 are the ones worth doing early: they are cheap, they make the record trustworthy even before anything is signed, and they cannot be retrofitted to terms already released without a gap in the chain.
