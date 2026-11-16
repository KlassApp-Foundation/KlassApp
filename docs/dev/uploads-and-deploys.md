# Uploaded files, deploys and the report card crest

Tracker items: **T-129** and **T-130** (uploads lost on deploy; the crest read from the wrong disk).

## What goes wrong

1. **The crest is read from a different disk than the one it is saved to.** School settings save the crest with `Storage::putFile()`, which uses the **default** disk (`config('filesystems.default')`, set by `FILESYSTEM_DISK`; `.env.example` has `local`, which is `storage/app`). The report card used to read the **`public`** disk (`storage/app/public`). On a school whose default disk is not `public`, a saved crest was never found and the card printed no crest.
2. **A local disk does not survive a deploy.** On Laravel Cloud the local filesystem is replaced on every deployment and is not shared between replicas (AGENTS.md rule 39). Any file saved to a `local` disk (the crest, photos, attachments) disappears at the next deploy, while the `school_details.school_logo` row still points at it.

## What the code does now (`App\Services\Schools\SchoolIdentity`, `App\View\Composers\ReportIdentityComposer`)

- The crest is looked up on the disk the upload used (the default disk), then on `public` (rows saved by older code). A remote disk (object storage) is copied to a temp file because the PDF renderer needs a local path.
- If nothing is saved, the row is the `-` placeholder, the file is gone, or the file is not an image, there is **no crest**, and the report card prints the **monogram of the school's initials** (the card template already does this when it has no crest path). A card is never blocked by a missing crest.
- The composer is registered for every view under `admin.marks.report-templates.*`, so the fix needs no change to the card service or the templates. It takes the school from the view's own `$school` (never from the signed-in user), so a PDF built by a queued job gets the right school.
- The same composer gives each template `$headTeacherName` (from school settings, `null` when not set). The templates print it.
- The settings screen **School details for report cards** (`/admin/school-identity`) says whether a crest is saved, saved but missing (so a school can see that a deploy lost it), or not set.

## What an operator must do (not done by this change)

Settings and environment variables belong to Rasta, so this is a report, not a change:

- To keep uploads across deploys the default disk must be the object-storage disk: set `FILESYSTEM_DISK=s3` on the environment (with the bucket attached; `config/filesystems.php` already reads `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`). Applies only after a real deployment.
- Files already saved on a local disk are not moved by this change. After switching, each school re-uploads its crest on **School details for report cards**; until then its cards show the initials.
- A migration of existing local files to the bucket would be an operations task (dry run first, verified backup, Rasta's approval).

## Tests

`tests/Feature/Reports/CrestAndHeadTeacherTest.php`: crest on the default disk found; legacy `public` crest found; the card service alone misses a default-disk crest (the bug); missing file / placeholder / not an image give no crest; another school's crest never used; the real progressive card prints the crest, then the initials after the file is deleted; head teacher name reaches the templates; composer registration; the screen's three crest states; saving; refusals for other roles.
Not tested here: a remote (object storage) disk, because the sandbox has none; the temp-copy path is exercised only by reading.
