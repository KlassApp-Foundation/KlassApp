# Test run on scratch/report-stack-2026-11-16

Database: SQLite in memory, the same connection `phpunit.xml` forces. PHP 8.4.27. This is the disposable scratch branch, not CI on a pull request.

## Suite without the 800-learner scale test

Command: `vendor/bin/phpunit --exclude-group scale --log-junit /tmp/junit-rest.xml`

Result: **3880 tests, 34363 assertions, 38 failures, 4 skipped, 5 incomplete, 2 risky.** Exit 1.

The five incomplete and two risky tests are PHPUnit markers, not named assertion failures. The 38 failures are below. "Owning PR" is the draft whose merge last edited that test file on this branch, or the production file named in the exception. Several failures are the drafts disagreeing with each other, so the last editor is not always the only author.

| Test | What failed | Owning PR |
| --- | --- | --- |
| `SignupEmailVerificationTest::test_resend_supersedes_the_previous_code` | Resend returned 429, not a redirect | Not changed on this branch |
| `MarksReleaseQueueTest::test_release_now_with_50_bound_parents_queues_50_jobs_and_returns_at_once` | 51 jobs queued, expected 50 | Not changed on this branch. The scratch release path also includes #1323 |
| `ExamSetPageAndRosterTest::test_a_base_class_exam_lists_students_from_every_stream` | The page HTML did not contain the expected roster | #1337 |
| `ExamTypeSettingsTest` (4 tests) | Weights that do not total 100 were accepted; the unset form did not start with the last set counting; renaming a starter set did not keep the catalog row; a head teacher got 403 where the test wanted 404 | #1330, then later marks drafts |
| `MarksFollowUpTest` (3 tests) | An absence was counted in the total (2, expected 1); a null mark broke the sheet or the release page; a mark above the maximum did not block submit | #1350 |
| `MarksOverviewTest` (3 tests) | The beginning/mid/end tabs did not switch as expected; an unsubmitted subject still counted (218, expected 50); the phone card did not include the KLS number the test looks for | #1337 |
| `SchoolExamSetTabsTest::test_a_released_set_shows_missing_and_an_open_set_keeps_a_dash` | The open set's cell said Missing, and the test wanted a dash | #1337 |
| `SetupRoutingTest::test_dashboard_has_no_prefer_to_complete_step_and_continue_setup_is_the_only_entry` | The setup bar had more than one link | Not changed on this branch |
| `ClassReportScreenTest` (4 tests) | Unreleased marks showed; a released sitting did not list each student as the test expected; another class's release opened this class; the class-PDF states were not the expected words | #1350 |
| `EndOfTermCompositionLineTest::test_the_printed_card_shows_the_mix_line_and_abs` | The printed card HTML did not contain the mix line the test looks for | #1339 |
| `SchoolExamSetReportScreenTest::test_a_school_with_five_named_sets_builds_one_combined_report` | 1 combined report, expected 2 | #1339 |
| `CrestAndHeadTeacherTest` head-teacher case | A head teacher received 200 on the school-details page. The test says only a school admin may change it | #1359, against the access added in #1325 |
| `MidExamNullScheduledAtReportTest::test_mid_exam_label_helpers_handle_null_and_dated_exams` | Label was `APR MID`, expected `APR Quiz Hour` | #1330 |
| `OneDivisionTableTest::test_every_screen_uses_the_schools_division_ranges` | A screen did not use the school's saved division ranges | #1350 |
| `ProgressiveDefaultSnapshotTest::test_default_rules_match_the_captured_card_and_pdf` | The rendered card is not the captured fixture. The first difference is whitespace around `END OF TERM EXAMINATION`; the diff continues into the totals row | #1345 |
| `ProgressiveParityTest::test_forty_learners_match_an_independent_progressive_calculation` | Learner 3 aggregate was 6, expected 5 | #1346. The primary default bands changed in #1361 |
| `ReportKindSettingTest::test_the_composition_columns_show_only_for_set_based_reports` | A progressive school got 200 where the test wanted 404 for the composition columns | #1342 |
| `ReportRolesTest` school-details case | A head teacher opened `/admin/school-identity` (200). The test says that role must not | #1359, against #1325 |
| `WholeClassReportCardPdfTest::test_class_head_downloads_their_own_class` | 500. `StudentReportCardService::examSetNames()` does not exist. Called from `Teacher/ReportCardsController.php` line 57 | The call was added in the commit "Use each school's exam set names", brought in by #1339. The method was never added |
| `ReportCompositionAndProfileTest` (2 tests) | A set that should stand alone was included in the total; a released end-of-term mark was 80, expected 74 | #1330. The scratch branch also removed a duplicate `use App\Models\MarksRelease` in this file so the suite could boot |
| `ResultsServiceTest::test_missing_and_absent_marks_count_as_zero` | Total 80, expected 40 | #1330 |
| `SchoolExamSetReportTest::test_five_school_named_sets_follow_the_combined_report_rule` | The combined report HTML did not match | #1330 |
| `NoMutatingGetRouteTest::test_no_get_or_head_route_maps_to_a_mutating_action` | `GET admin/classes/link-section/{section}` maps to `ClassLinkController@confirm` | Not changed on this branch. The confirm page is the one #1356 added a button on |
| `SetupActionsTest` head-teacher case | A head teacher received 200 for an action the test says only an admin may take | #1356, against the access added in #1325 |
| `ClassTeacherExamCustodianTest::test_combined_marksheet_allows_class_teacher_via_shared_helper_path` | 500. PhpSpreadsheet: no worksheet to make active | Not changed on this branch |
| `MarkAllPresentBrowserTest::test_mark_all_present_flips_visible_radios_without_saving` | The browser script could not find the package `puppeteer-core` | Not changed on this branch. This machine has Playwright, not that package |
| `TeacherReportCardsTest::test_ct_sees_only_own_class_on_index` | 500, same missing `examSetNames()` as the whole-class download | #1339 |

## ReportScaleSmokeTest, run alone

Command: `php -d memory_limit=-1 vendor/bin/phpunit --filter ReportScaleSmokeTest`

Result: **OK. 1 test, 4835 assertions. 44.6 seconds. Peak memory reported by PHPUnit: 1023.85 MB.** Exit 0.

Times printed by the test, in seconds:

| Step | Seconds |
| --- | --- |
| Generate 800 learners | 1.6 |
| 10 results sheets | 1.6 |
| 10 learner PDFs | 4.5 |
| One whole-class PDF | 35.9 |

The earlier full-suite run, with `memory_limit=2G`, died inside this same test with "Premature end of PHP process" and wrote no JUnit file. Alone, with the limit removed, the test finishes. Owning draft of the test: #1358.
