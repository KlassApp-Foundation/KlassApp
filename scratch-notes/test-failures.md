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

A second run with `--exclude-group scale` was started so the other failures could be named. If that run finished, its list is below. If this file still says the run is open, the names were not available when the branch was pushed.

Second run: see the end of this file.
