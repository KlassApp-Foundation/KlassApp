<?php

namespace Tests\Feature\Onboarding;

use App\Services\Onboarding\ChecklistWorkbookConverter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Tests\TestCase;

/** T-079: checklist:convert. Synthetic, fictional school only. */
class ChecklistConvertTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/checklist-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/{,*/}*', GLOB_BRACE) ?: [] as $f) {
            is_file($f) && unlink($f);
        }
        foreach (['/out/no-importer', '/out/pending', '/out'] as $d) {
            @rmdir($this->dir.$d);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function addTab(Spreadsheet $book, string $title, array $rows, bool $first = false): void
    {
        $ws = $first ? $book->getActiveSheet() : $book->createSheet();
        $ws->setTitle($title);
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $v) {
                $ws->setCellValueExplicit([$c + 1, $r + 1], $v, is_int($v) || is_float($v) ? 'n' : 's');
            }
        }
        foreach ($rows as $r => $row) {
            if (($row[0] ?? '') === 'Example test') {
                $ws->getStyle('A'.($r + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
            }
        }
        if (isset($rows[1])) {
            $ws->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
        }
    }

    /** Builds a fictional-school workbook. $bad adds the broken rows. */
    private function workbook(bool $bad = true, string $dpa = 'Yes', bool $emptyLearners = false, ?array $termsRows = null): string
    {
        $b = new Spreadsheet;
        $this->addTab($b, 'School Details', [['Field', 'Answer'], ['School name', 'Fictional Hills Primary'], ['Country', 'Uganda']], true);
        $this->addTab($b, 'Admin & Contacts', array_merge(
            [['Role *', 'Full name *', 'Email', 'Phone', 'WhatsApp on this number?', 'Notes'], ['Example', 'Example Person', 'x@example.org', '0700000000', 'Yes', '']],
            [['Head teacher', 'Hope Example', 'hope@example.org', '0772 123456', 'Yes', '']],
            array_fill(0, 10, ['', '', '', '', '', '']),
            [['School WhatsApp number', '0772 000111']],
        ));
        $this->addTab($b, 'Terms & Exams', $termsRows ?? [
            ['Academic year *', 'Term *', 'Start date *', 'End date *', 'Notes'],
            ['2026', 'Term 1', '01/02/2026', '30/04/2026', ''],
            ['2026', 'Term 1b', '03/02/2026', '31/12/2025', ''],
            ['2026', 'Term 2', '25/05/2026', '14/08/2026', ''],
            ['', '', '', '', ''],
            ['Assessment name *', 'Classes it applies to', 'Out of (marks)', 'Counts on the report card?', 'Notes'],
            ['Example test', 'All', 20, 'Yes', ''],
            ['Beginning of term test', 'All', 30, 'Yes', ''],
            ['', '', '', '', ''],
            ['Grading', 'A 80-100, B 70-79, C 60-69'],
        ]);
        $this->addTab($b, 'Classes & Streams', [
            ['Class *', 'Stream', 'Number of learners (approx.)', 'Class teacher full name', 'Class teacher email', 'Notes'],
            ['Example', 'A', 10, '', '', ''],
            ['Baby Class', '', 8, 'Tess Teacher', 'tess@example.org', ''],
            ['Primary One', 'East', 6, 'Omar Mentor', '', ''],
            ['Primary One', 'West', 6, 'Nobody Known', '', ''],
            ['P.3', 'A', 10, 'Omar Mentor', '', ''],
        ]);
        $this->addTab($b, 'Subjects', [
            ['Subject or learning area *', 'Section', 'Classes that take it', 'Counts on report card total?', 'Short code', 'Notes'],
            ['Example', 'Core', 'All', 'Yes', 'EX', ''],
            ['Mathematics', 'Core', 'Primary One', 'Yes', 'MTC', ''],
            ['English', 'Core', 'Primary One', 'Yes', 'ENG', ''],
        ]);
        $this->addTab($b, 'Teachers', [
            ['Full name *', 'Email *', 'Phone', 'Gender', 'Role', 'Notes'],
            ['Example', 'e@example.org', '', '', '', ''],
            ['Tess Teacher', 'tess@example.org', '0772 123 456', 'Female', 'Class teacher', ''],
            ['Omar Mentor', 'omar@example.org', '+256 772 765432', 'Male', 'Teacher', ''],
            ['Pat Pending', 'pat@example.org', $bad ? '12345' : '0772 555111', '', '', ''],
        ]);
        $this->addTab($b, 'Teaching Assignments', array_merge(
            [['Teacher full name *', 'Class *', 'Stream', 'Subject *', 'Notes'], ['Example', 'Primary One', 'A', 'Math', ''],
                ['Omar Mentor', 'Primary One', 'East', 'Mathematics', ''],
                ['omar  mentor', 'Primary One', 'West', 'English', '']],
            $bad ? [['Ghost Teacher', 'Primary One', 'East', 'Mathematics', ''], ['Tess Teacher', 'Primary One', 'East', 'Woodwork', '']] : [],
        ));

        $learners = [['Learner full name *', 'Class *', 'Stream', 'Gender', 'Date of birth', 'Parent / guardian full name', 'Parent / guardian phone', 'Relationship', 'Parent / guardian email', 'Second parent / guardian name', 'Second parent / guardian phone', 'Existing school ID number', 'Notes'],
            ['Example Child', 'Baby Class', '', 'Male', '01/01/2020', 'Parent', '0700000000', '', '', '', '', '', '']];
        $mk = fn (int $i, string $class, string $stream) => ["Learner {$i} Example", $class, $stream, $i % 2 ? 'Male' : 'Female', '15/03/20'.(14 + $i % 3), "Guardian {$i}", '0772 '.str_pad((string) (100000 + $i), 6, '0'), $i % 3 === 0 ? 'Mother' : '', $i % 3 === 0 ? "g{$i}@example.org" : '', $i === 5 ? 'Second Guardian' : '', $i === 5 ? '0772 222333' : '', '', ''];
        for ($i = 1; $i <= 8; $i++) {
            $learners[] = $mk($i, 'Baby Class', '');
        }
        for ($i = 9; $i <= 20; $i++) {
            $learners[] = $mk($i, 'Primary One', $i <= 14 ? 'East' : 'West');
        }
        for ($i = 21; $i <= 30; $i++) {
            $learners[] = $mk($i, 'Primary Three', 'A');
        }
        if ($bad) {
            $learners[] = ['Wrong Class Child', 'Primary Eight', 'A', 'Male', '', 'G', '0772 000000', '', '', '', '', '', ''];
            $learners[] = ['', 'Baby Class', '', 'Male', '', '', '', '', '', '', '', '', ''];  // empty first column: skipped silently
            $learners[] = ['Bad Phone Child', 'Baby Class', '', 'Male', '15/03/2015', 'Guardian', '12345', '', '', '', '', '', ''];
            $learners[] = ['Missing Class Child', '', '', 'Male', '', '', '', '', '', '', '', '', ''];
        }
        $this->addTab($b, 'All Learners (for import)', $emptyLearners ? [$learners[0], $learners[1]] : $learners);
        $this->addTab($b, 'Baby Class', $emptyLearners ? [['Learner *'], ['Example'], ['Someone Present']] : [['Learner *'], ['Example']]);
        $this->addTab($b, 'Fees', [
            ['Fee name *', 'Class *', 'Amount *', 'Currency', 'Duration (Termly, One-off, Yearly) *', 'Compulsory?', 'Notes'],
            ['Example', 'All', 1, 'UGX', 'Termly', 'Yes', ''],
            ['Tuition', 'Primary One', 250000, 'UGX', 'Termly', 'Yes', ''],
        ]);
        $this->addTab($b, 'Confirmations', [['Statement', 'Answer'], ['Example', 'No'], ['We agree to the data processing agreement', $dpa], ['Contact details are correct', 'Yes']]);

        $path = $this->dir.'/checklist.xlsx';
        IOFactory::createWriter($b, 'Xlsx')->save($path);

        return $path;
    }

    private function csv(string $path): array
    {
        return array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    public function test_T079_converts_good_rows_into_importer_files_with_exact_headers(): void
    {
        $this->artisan('checklist:convert', ['file' => $this->workbook(true), '--out' => $this->dir.'/out'])->assertExitCode(0);

        $students = $this->csv($this->dir.'/out/students.csv');
        $this->assertSame(['Name', 'Class', 'Stream', 'Parent Name', 'Parent Phone', 'Gender', 'Date of Birth', 'School Student ID'], $students[0]);
        $names = array_column($students, 0);
        $this->assertNotContains('Example Child', $names, 'the grey example row is skipped');
        $this->assertContains('Learner 1 Example', $names);
        $this->assertSame('+256772100001', $students[1][4], 'local phone becomes E.164');
        $this->assertSame('2015-03-15', $students[1][6]);
        // 30 good + the bad-phone child (kept, phone blank); wrong class / missing class are excluded.
        $this->assertCount(31, array_slice($students, 1));
        $this->assertNotContains('Wrong Class Child', $names);

        $this->assertSame(['Name', 'Email', 'Subjects', 'Classes', 'Phone'], $this->csv($this->dir.'/out/teachers.csv')[0]);
        $this->assertSame(['class', 'stream', 'name', 'email', 'phone'], $this->csv($this->dir.'/out/class-teachers.csv')[0]);
        $links = $this->csv($this->dir.'/out/teacher-links.csv');
        $this->assertSame(['Teacher', 'Subject', 'Class', 'Stream', 'Phone'], $links[0]);
        $this->assertCount(3, $links, 'two matched assignments; the unmatched two are not written');
        $this->assertSame('Omar Mentor', $links[2][0], 'name match ignores case and extra spaces');
        $this->assertFileExists($this->dir.'/out/pending/learner-guardian-details.csv');
        $this->assertFileExists($this->dir.'/out/pending/assessments.csv');
        $this->assertStringContainsString('A 80-100', file_get_contents($this->dir.'/out/pending/grading.txt'));
    }

    public function test_T079_report_flags_every_bad_row_with_tab_and_row_number(): void
    {
        $this->artisan('checklist:convert', ['file' => $this->workbook(true), '--out' => $this->dir.'/out'])->assertExitCode(0);
        $report = file_get_contents($this->dir.'/out/report.md');

        $this->assertStringContainsString('"Primary Eight" is not a KlassApp class name', $report);
        $this->assertStringContainsString('"12345" not converted', $report);
        $this->assertStringContainsString('Missing required value in Class', $report);
        $this->assertStringContainsString('"Ghost Teacher" does not match any name on the Teachers tab', $report);
        $this->assertStringContainsString('"Woodwork" does not match any subject on the Subjects tab', $report);
        $this->assertStringContainsString('"Nobody Known" does not match any name on the Teachers tab', $report);
        $this->assertStringContainsString('End date is before the start date', $report);
        $this->assertStringContainsString('Primary One / East | 6', $report);
        $this->assertStringContainsString('Pending for manual setup', $report);
        $this->assertStringContainsString('"P.3" written as Primary Three', $report);
    }

    public function test_T079_refuses_when_the_data_processing_agreement_is_not_yes(): void
    {
        $this->artisan('checklist:convert', ['file' => $this->workbook(false, 'No'), '--out' => $this->dir.'/out'])
            ->expectsOutputToContain('data processing agreement')
            ->assertExitCode(1);
        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    public function test_T079_stops_with_an_error_when_all_learners_is_empty_but_a_class_tab_has_rows(): void
    {
        $this->artisan('checklist:convert', ['file' => $this->workbook(false, 'Yes', true), '--out' => $this->dir.'/out'])
            ->expectsOutputToContain('formula values')
            ->assertExitCode(1);
        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    public function test_T079_does_not_invent_a_country_for_local_phones(): void
    {
        $r = (new ChecklistWorkbookConverter)->convert($this->workbook(false));
        $this->assertNull($r['blocked']);
        $this->assertSame('+256772123456', $r['files']['teachers.csv'][0][4]);
    }

    public function test_terms_stop_before_the_exams_block_on_the_real_template_layout(): void
    {
        $blank = ['', '', '', '', ''];
        $filled = [
            ['Academic year *', 'Term *', 'Start date *', 'End date *', 'Notes'],
            ['2027', 'Term 1', '2027-02-08', '2027-05-07', ''],
            ['2026', 'Term 1', '2026-02-02', '2026-05-01', ''],
            ['2026', 'Term 2', '2026-05-25', '2026-08-14', ''],
            ['2026', 'Term 3', '2026-09-07', '2026-12-04', ''],
            $blank, $blank, $blank, $blank, $blank,
            ['Exams / assessments you give each term', '', '', '', ''],
            ['Assessment name *', 'Classes it applies to *', 'Out of (marks) *', 'Counts on the report card? *', 'Notes'],
            ['End of Term', 'All', '100', 'Yes', ''],
            $blank,
            ['Grading', 'A 80-100 Distinction', '', '', ''],
            ['Keep this line with the scale.', '', '', '', ''],
        ];
        $read = (new ChecklistWorkbookConverter)->convert($this->workbook(false, 'Yes', false, $filled));
        $this->assertNull($read['blocked']);
        foreach ($read['flags'] as $flag) {
            $this->assertStringNotContainsString('Missing required value in Term', (string) $flag['message']);
            $this->assertStringNotContainsString('Start or end date missing', (string) $flag['message']);
        }
        $this->assertSame(['Term 1', 'Term 2', 'Term 3'], array_column($read['planRows']['terms'], 'name'));
        $this->assertSame('End of Term', $read['pending']['assessments.csv'][1][0]);
        $grading = strtolower((string) $read['pending']['grading.txt'][0][0]);
        $this->assertStringContainsString('a 80-100 distinction', $grading);
        $this->assertStringContainsString('keep this line with the scale.', $grading);

        $template = [
            ['Academic year *', 'Term *', 'Start date *', 'End date *', 'Notes'],
            ['2027', 'Term 1', '2027-02-08', '2027-05-07', ''],
            $blank, $blank, $blank, $blank, $blank, $blank, $blank, $blank,
            ['Exams / assessments you give each term', '', '', '', ''],
            ['Assessment name *', 'Classes it applies to *', 'Out of (marks) *', 'Counts on the report card? *', 'Notes'],
            ['End of Term', 'All', '100', 'Yes', ''],
        ];
        $untouched = (new ChecklistWorkbookConverter)->convert($this->workbook(false, 'Yes', false, $template));
        $this->assertNull($untouched['blocked']);
        $this->assertSame([], array_column($untouched['planRows']['terms'], 'name'));
        foreach ($untouched['flags'] as $flag) {
            $this->assertStringNotContainsString('Missing required value in Term', (string) $flag['message']);
            $this->assertStringNotContainsString('Start or end date missing', (string) $flag['message']);
        }
        $this->assertSame('End of Term', $untouched['pending']['assessments.csv'][1][0]);
    }

    public function test_T079_refuses_to_overwrite_a_non_empty_folder_without_force(): void
    {
        mkdir($this->dir.'/out');
        file_put_contents($this->dir.'/out/keep.txt', 'x');
        $this->artisan('checklist:convert', ['file' => $this->workbook(false), '--out' => $this->dir.'/out'])->assertExitCode(1);
        $this->assertFileExists($this->dir.'/out/keep.txt');
    }
}
