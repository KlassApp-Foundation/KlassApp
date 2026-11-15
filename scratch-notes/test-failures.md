# Test run on scratch/report-stack-2026-11-16

Database: SQLite in memory, the same `DB_CONNECTION` PHPUnit forces in `phpunit.xml` (that is what CI's full suite uses). PHP 8.4.27. `vendor/bin/phpunit` with `memory_limit=2G`.

## Full suite

The full run died before it could write a JUnit file.

- Progress when it died: 2562 / 3881 tests (66%).
- Last line: `Fatal error: Premature end of PHP process when running Tests\Feature\Reports\ReportScaleSmokeTest::test_eight_hundred_learners_go_through_sheets_class_pdfs_and_learner_pdfs.`
- Exit code 2. `/tmp/junit.xml` was empty, so the failure names before that crash were not captured. The dot log shows failures (red `F`) before the crash, including a cluster around the 1000th test, but the names are not in the log.
- That scale test is the 800-learner smoke from #1358 (`tests/Feature/Reports/ReportScaleSmokeTest.php`). On this machine the PHP process ended inside it. Acceptance check 7 (about 800 learners) was not timed because of that.

## Scratch-only fix already applied

`tests/Feature/Results/ReportCompositionAndProfileTest.php` imported `App\Models\MarksRelease` twice after the #1330 merge. PHP refused to boot the suite until the duplicate `use` was removed. Owning pull request: #1330. The author should drop the second import when rebasing.

## Second run

A second run with `--exclude-group scale` was started so the other failures could be named. When this note was written it was still inside the demo-school seeders, at 915 / 3880 (23%), and had not printed a failure summary. The JUnit file is written at the end, so the names of the red tests are not in this file.

What is known:

- The suite does not boot until the duplicate `use App\Models\MarksRelease` is removed (#1330).
- The 800-learner scale test ends the PHP process on this machine (#1358).
- The dot log from the first run shows failures before that crash. Their names were not printed.
