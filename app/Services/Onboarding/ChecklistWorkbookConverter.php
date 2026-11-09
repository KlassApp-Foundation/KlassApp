<?php

namespace App\Services\Onboarding;

use App\Services\Phones\PhoneE164Converter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * T-079 groundwork: reads a school's filled onboarding checklist workbook (.xlsx exported from
 * Google Sheets) and produces, in memory, the rows for the files the existing importers take,
 * plus a validation report. It never touches the database and never invents data: anything that
 * does not fit is flagged with its tab and row number.
 *
 * Layout assumed for every data tab: row 1 header, row 2 grey example row (skipped), data from
 * row 3. A header ending " *" is required. Rows whose first column is empty are skipped.
 */
final class ChecklistWorkbookConverter
{
    /** Canonical section names, as seeded by AcademicSetupService. */
    public const CANONICAL_CLASSES = [
        'Baby Class', 'Middle Class', 'Top Class',
        'Primary One', 'Primary Two', 'Primary Three', 'Primary Four',
        'Primary Five', 'Primary Six', 'Primary Seven',
        'Senior One', 'Senior Two', 'Senior Three', 'Senior Four', 'Senior Five', 'Senior Six',
    ];

    private const WORDS = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven'];

    /** Importer file => headers, taken from the importers/templates (see the PR for the sources). */
    public const FILES = [
        'students.csv' => ['Name', 'Class', 'Stream', 'Parent Name', 'Parent Phone', 'Gender', 'Date of Birth', 'School Student ID'],
        'teachers.csv' => ['Name', 'Email', 'Subjects', 'Classes', 'Phone'],
        'teacher-links.csv' => ['Teacher', 'Subject', 'Class', 'Stream', 'Phone'],
        'class-teachers.csv' => ['class', 'stream', 'name', 'email', 'phone'],
    ];

    /** @var list<array{tab:string,row:?int,field:string,message:string}> */
    private array $flags = [];

    /** @var array<string,array{read:int,accepted:int}> */
    private array $counts = [];

    private ?string $country = null;

    private ?int $dpaRow = null;

    private string $dpaAnswer = '';

    /**
     * @return array{
     *   blocked: ?string, files: array<string,list<list<string>>>, noImporter: array<string,list<list<string>>>,
     *   pending: array<string,list<list<string>>>, flags: list<array>, counts: array, learnersByClass: array<string,int>,
     *   pendingNotes: list<string>, school: string
     * }
     */
    public function convert(string $path): array
    {
        $this->flags = [];
        $this->counts = [];
        $reader = IOFactory::createReaderForFile($path);
        $book = $reader->load($path);

        $out = [
            'blocked' => null, 'files' => [], 'noImporter' => [], 'pending' => [], 'flags' => [],
            'counts' => [], 'learnersByClass' => [], 'pendingNotes' => [], 'school' => '',
        ];

        $details = $this->formPairs($this->sheet($book, 'School Details'));
        $out['school'] = $this->pairValue($details, 'school name') ?? '';
        $this->country = $this->pairValue($details, 'country');
        if ($this->country === null) {
            $this->flag('School Details', null, 'Country', 'No country given, so local phone numbers cannot be completed; only numbers starting with + or 00 are converted.');
        }

        $out['blocked'] = $this->checkConfirmations($this->sheet($book, 'Confirmations'));
        if ($out['blocked'] !== null) {
            $out['flags'] = $this->flags;
            $out['dpa'] = $this->dpaPayload();

            return $out;
        }

        $learnersSheet = $this->sheetStartingWith($book, 'All Learners');
        $learnerRows = $learnersSheet ? $this->table($learnersSheet) : null;
        if ($learnerRows === null || $learnerRows['rows'] === []) {
            $filled = $this->classTabsWithRows($book);
            if ($filled !== []) {
                $out['blocked'] = 'The "All Learners (for import)" tab is empty but these class tabs have rows: '.implode(', ', $filled)
                    .'. The formula values probably did not export; re-export the workbook with values (File > Download > Microsoft Excel after the sheet has finished calculating) and run again.';
                $out['flags'] = $this->flags;
                $out['dpa'] = $this->dpaPayload();

                return $out;
            }
        }

        $classes = $this->classes($this->sheet($book, 'Classes & Streams'));
        $subjects = $this->subjects($this->sheet($book, 'Subjects'), $out);
        $teachers = $this->teachers($this->sheet($book, 'Teachers'), $out);
        $links = $this->assignments($this->sheet($book, 'Teaching Assignments'), $teachers, $subjects, $classes);

        // Teachers file: aggregate subjects/classes from the matched assignments.
        $teacherRows = [];
        foreach ($teachers['list'] as $t) {
            $a = $links['byTeacher'][$t['key']] ?? ['subjects' => [], 'classes' => []];
            $teacherRows[] = [$t['name'], $t['email'], implode(', ', $a['subjects']), implode(', ', $a['classes']), $t['phone']];
        }
        $out['files']['teachers.csv'] = $teacherRows;
        $out['files']['teacher-links.csv'] = $links['rows'];
        $out['pending']['teacher-details.csv'] = array_merge([['Name', 'Gender', 'Role']], array_map(fn ($t) => [$t['name'], $t['gender'], $t['role']], $teachers['list']));

        $ct = $this->classTeachers($classes, $teachers);
        $out['files']['class-teachers.csv'] = $ct;
        $out['noImporter']['classes-and-streams.csv'] = array_merge([['Class', 'Stream', 'Learners (approx.)']], array_map(fn ($c) => [$c['class'], $c['stream'], $c['count']], $classes['list']));
        $out['noImporter']['subjects.csv'] = $subjects['csv'];

        [$students, $guardian, $byClass, $learnerPlan] = $this->learners($learnerRows, $classes);
        $out['files']['students.csv'] = $students;
        $out['pending']['learner-guardian-details.csv'] = $guardian;
        $out['learnersByClass'] = $byClass;

        $termPlan = $this->terms($this->sheet($book, 'Terms & Exams'), $out);
        $this->fees($this->sheet($book, 'Fees'), $classes, $out);
        $this->adminContacts($this->sheet($book, 'Admin & Contacts'), $out);

        // Expected (approx.) vs actual learners per class and stream.
        foreach ($classes['list'] as $c) {
            $actual = $byClass[$c['class'].($c['stream'] !== '' ? ' / '.$c['stream'] : '')] ?? 0;
            if ($c['count'] !== null && $actual !== $c['count']) {
                $this->flag('Classes & Streams', $c['row'], 'Number of learners (approx.)', "Says about {$c['count']} but {$actual} learners were found in All Learners for ".trim($c['class'].' '.$c['stream']).'.', 'info');
            }
        }

        $out['pendingNotes'] = [
            'Parent / guardian email, relationship and the second parent / guardian: the student importer has no column for them. Full list in pending/learner-guardian-details.csv.',
            'Teacher gender and role: the teacher importer has no column for them. Full list in pending/teacher-details.csv.',
            'Teaching assignment stream: the teacher-link importer reads class only; the stream is kept in the file but ignored on import.',
        ];

        $out['flags'] = $this->flags;
        $out['counts'] = $this->counts;
        $out['dpa'] = $this->dpaPayload();
        $out['planRows'] = [
            'classes' => $classes['list'],
            'subjects' => $subjects['plan'] ?? [],
            'teachers' => $teachers['list'],
            'assignments' => $links['plan'] ?? [],
            'students' => $learnerPlan,
            'terms' => $termPlan,
        ];

        return $out;
    }

    /** Renders report.md. */
    public function report(array $r, string $source): string
    {
        $md = "# Checklist conversion report\n\nSource: `{$source}`\n";
        if ($r['school'] !== '') {
            $md .= "School: {$r['school']}\n";
        }
        if ($r['blocked'] !== null) {
            return $md."\n## Refused\n\n{$r['blocked']}\n\nNo import files were written.\n";
        }

        $md .= "\n## Counts per tab\n\n| Tab | Rows read | Accepted | Flagged |\n|---|---|---|---|\n";
        foreach ($r['counts'] as $tab => $c) {
            $flagged = count(array_filter($r['flags'], fn ($f) => $f['tab'] === $tab && $f['row'] !== null && ($f['severity'] ?? 'error') !== 'info'));
            $md .= "| {$tab} | {$c['read']} | {$c['accepted']} | {$flagged} |\n";
        }

        $md .= "\n## Learners per class and stream\n\n| Class / stream | Learners |\n|---|---|\n";
        foreach ($r['learnersByClass'] as $k => $n) {
            $md .= "| {$k} | {$n} |\n";
        }

        $errors = array_filter($r['flags'], fn ($f) => ($f['severity'] ?? 'error') === 'error');
        $info = array_filter($r['flags'], fn ($f) => ($f['severity'] ?? 'error') === 'info');
        $md .= "\n## Flagged rows (not guessed; fix in the sheet and run again)\n\n";
        $md .= $errors === [] ? "None.\n" : "| Tab | Row | Field | Problem |\n|---|---|---|---|\n";
        foreach ($errors as $f) {
            $md .= '| '.$f['tab'].' | '.($f['row'] ?? '-').' | '.$f['field'].' | '.str_replace('|', '/', $f['message'])." |\n";
        }
        $missing = array_filter($errors, fn ($f) => str_starts_with($f['message'], 'Missing required'));
        $md .= "\n## Missing required fields\n\n";
        $md .= $missing === [] ? "None.\n" : implode("\n", array_map(fn ($f) => "- {$f['tab']} row ".($f['row'] ?? '-').": {$f['field']}", $missing))."\n";
        if ($info !== []) {
            $md .= "\n## Notes\n\n".implode("\n", array_map(fn ($f) => "- {$f['tab']} row ".($f['row'] ?? '-').": {$f['message']}", $info))."\n";
        }

        $md .= "\n## Pending for manual setup\n\n".implode("\n", array_map(fn ($n) => "- {$n}", $r['pendingNotes']))."\n";
        foreach ($r['pending'] as $name => $rows) {
            if ($name === 'assessments.csv') {
                $md .= '- Assessments ('.(count($rows) - 1)." rows): the exam importer takes Term/Type/Status/Level/Class/Subject/Teacher, not assessment definitions. See pending/assessments.csv.\n";
            }
        }
        if (isset($r['pending']['grading.txt'])) {
            $md .= "- Grading notes (free text): see pending/grading.txt.\n";
        }
        $md .= "- Terms, subjects, classes and streams, fees and admin contacts have no file importer in this codebase. Clean copies are in no-importer/.\n";

        $md .= "\n## Files written\n\n";
        foreach ($r['files'] as $name => $rows) {
            $md .= "- `{$name}`: ".count($rows)." rows\n";
        }

        return $md;
    }

    // ------------------------------------------------------------------ tabs

    private function checkConfirmations(?Worksheet $ws): ?string
    {
        if ($ws === null) {
            return 'The Confirmations tab is missing, so the data processing agreement cannot be checked.';
        }
        $found = false;
        foreach ($ws->toArray(null, true, false, false) as $i => $row) {
            $label = strtolower($this->text($row[0] ?? ''));
            $answer = strtolower($this->text($row[1] ?? ''));
            if ($label === '') {
                continue;
            }
            if (str_contains($label, 'data processing')) {
                $found = true;
                $this->dpaRow = $i + 1;
                $this->dpaAnswer = $answer === '' ? 'blank' : $this->text($row[1] ?? '');
                if ($answer !== 'yes') {
                    return 'The data processing agreement row on Confirmations (row '.($i + 1).') is "'.($this->dpaAnswer === '' ? 'blank' : $this->dpaAnswer).'", not Yes.';
                }
                $this->dpaAnswer = 'Yes';
            } elseif ($i > 1 && $answer !== '' && $answer !== 'yes') {
                $this->flag('Confirmations', $i + 1, 'Answer', "Confirmation \"{$this->text($row[0])}\" is not Yes.", 'info');
            }
        }

        return $found ? null : 'There is no data processing agreement row on the Confirmations tab.';
    }

    /**
     * @return array{tab: string, row: ?int, answer: string}
     */
    private function dpaPayload(): array
    {
        return [
            'tab' => 'Confirmations',
            'row' => $this->dpaRow,
            'answer' => $this->dpaAnswer,
        ];
    }

    private function classes(?Worksheet $ws): array
    {
        $res = ['list' => [], 'set' => []];
        $t = $this->table($ws);
        $tab = 'Classes & Streams';
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            $row = $r['n'];
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $class = $this->canonicalClass($t['get']($r, 'class'), $tab, $row);
            if ($class === null) {
                continue;
            }
            $stream = $t['get']($r, 'stream');
            $count = $t['get']($r, 'number of learners (approx.)');
            $res['list'][] = [
                'class' => $class, 'stream' => $stream, 'count' => is_numeric($count) ? (int) $count : null, 'row' => $row,
                'teacher' => $t['get']($r, 'class teacher full name'), 'teacherEmail' => $t['get']($r, 'class teacher email'),
            ];
            $res['set'][$class.'|'.strtolower($stream)] = true;
            $this->accept($tab);
        }

        return $res;
    }

    private function subjects(?Worksheet $ws, array &$out): array
    {
        $res = ['names' => [], 'plan' => [], 'csv' => [['Subject', 'Section', 'Classes', 'Counts on report card total', 'Short code']]];
        $t = $this->table($ws);
        $tab = 'Subjects';
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $name = $t['get']($r, 'subject or learning area');
            $res['names'][$this->key($name)] = $name;
            $res['plan'][] = [
                'row' => $r['n'],
                'name' => $name,
                'classes' => $t['get']($r, 'classes that take it'),
            ];
            $res['csv'][] = [$name, $t['get']($r, 'section'), $t['get']($r, 'classes that take it'), $t['get']($r, 'counts on report card total?'), $t['get']($r, 'short code')];
            $this->accept($tab);
        }

        return $res;
    }

    private function teachers(?Worksheet $ws, array &$out): array
    {
        $res = ['list' => [], 'byKey' => []];
        $t = $this->table($ws);
        $tab = 'Teachers';
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $name = $t['get']($r, 'full name');
            $key = $this->key($name);
            if (isset($res['byKey'][$key])) {
                $this->flag($tab, $r['n'], 'Full name', "Duplicate teacher \"{$name}\" (first at row {$res['byKey'][$key]['row']}); kept the first.");

                continue;
            }
            $email = $t['get']($r, 'email');
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->flag($tab, $r['n'], 'Email', "\"{$email}\" is not a valid email address; left blank.");
                $email = '';
            }
            $phone = $this->phone($t['get']($r, 'phone'), $tab, $r['n'], 'Phone');
            $item = ['name' => $name, 'key' => $key, 'email' => $email, 'phone' => $phone, 'gender' => $t['get']($r, 'gender'), 'role' => $t['get']($r, 'role'), 'row' => $r['n']];
            $res['list'][] = $item;
            $res['byKey'][$key] = $item;
            $this->accept($tab);
        }

        return $res;
    }

    private function assignments(?Worksheet $ws, array $teachers, array $subjects, array $classes): array
    {
        $res = ['rows' => [], 'byTeacher' => [], 'plan' => []];
        $t = $this->table($ws);
        $tab = 'Teaching Assignments';
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            $n = $r['n'];
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $name = $t['get']($r, 'teacher full name');
            $teacher = $teachers['byKey'][$this->key($name)] ?? null;
            $ok = true;
            if ($teacher === null) {
                $this->flag($tab, $n, 'Teacher full name', "\"{$name}\" does not match any name on the Teachers tab.");
                $ok = false;
            }
            $subject = $t['get']($r, 'subject');
            $subjectName = $subjects['names'][$this->key($subject)] ?? null;
            if ($subjectName === null) {
                $this->flag($tab, $n, 'Subject', "\"{$subject}\" does not match any subject on the Subjects tab.");
                $ok = false;
            }
            $class = $this->canonicalClass($t['get']($r, 'class'), $tab, $n);
            $stream = $t['get']($r, 'stream');
            if ($class === null) {
                $ok = false;
            } elseif (! isset($classes['set'][$class.'|'.strtolower($stream)])) {
                $this->flag($tab, $n, 'Stream', "{$class} stream \"{$stream}\" is not on the Classes & Streams tab.");
                $ok = false;
            }
            if (! $ok) {
                continue;
            }
            $res['rows'][] = [$teacher['name'], $subjectName, $class, $stream, $teacher['phone']];
            $res['plan'][] = [
                'row' => $n,
                'teacher' => $teacher['name'],
                'subject' => $subjectName,
                'class' => $class,
                'stream' => $stream,
            ];
            $a = &$res['byTeacher'][$teacher['key']];
            $a['subjects'] = array_values(array_unique(array_merge($a['subjects'] ?? [], [$subjectName])));
            $a['classes'] = array_values(array_unique(array_merge($a['classes'] ?? [], [$class])));
            unset($a);
            $this->accept($tab);
        }

        return $res;
    }

    private function classTeachers(array $classes, array $teachers): array
    {
        $rows = [];
        foreach ($classes['list'] as $c) {
            if ($c['teacher'] === '') {
                continue;
            }
            $t = $teachers['byKey'][$this->key($c['teacher'])] ?? null;
            if ($t === null) {
                $this->flag('Classes & Streams', $c['row'], 'Class teacher full name', "\"{$c['teacher']}\" does not match any name on the Teachers tab.");

                continue;
            }
            $email = $c['teacherEmail'] !== '' ? $c['teacherEmail'] : $t['email'];
            $rows[] = [$c['class'], $c['stream'], $t['name'], $email, $t['phone']];
        }

        return $rows;
    }

    /** @return array{0: list<list<string>>, 1: list<list<string>>, 2: array<string,int>, 3: list<array<string, mixed>>} */
    private function learners(?array $t, array $classes): array
    {
        $students = [];
        $plan = [];
        $guardian = [['Learner', 'Class', 'Stream', 'Parent / guardian email', 'Relationship', 'Second parent / guardian name', 'Second parent / guardian phone']];
        $byClass = [];
        $seen = [];
        $tab = 'Learners';
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            $n = $r['n'];
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $name = $t['get']($r, 'learner full name');
            $class = $this->canonicalClass($t['get']($r, 'class'), $tab, $n);
            if ($class === null) {
                continue;
            }
            $stream = $t['get']($r, 'stream');
            if ($classes['list'] !== [] && ! isset($classes['set'][$class.'|'.strtolower($stream)])) {
                $this->flag($tab, $n, 'Stream', "{$class} stream \"{$stream}\" is not on the Classes & Streams tab.");

                continue;
            }
            $gender = $t['get']($r, 'gender');
            $g = ['m' => 'Male', 'male' => 'Male', 'f' => 'Female', 'female' => 'Female'][strtolower($gender)] ?? '';
            if ($gender !== '' && $g === '') {
                $this->flag($tab, $n, 'Gender', "\"{$gender}\" is not Male or Female; left blank.");
            }
            $dob = $this->date($t['raw']($r, 'date of birth'));
            if ($t['get']($r, 'date of birth') !== '' && $dob === null) {
                $this->flag($tab, $n, 'Date of birth', "\"{$t['get']($r, 'date of birth')}\" is not a date (expected day/month/year); left blank.");
            }
            $phone = $this->phone($t['get']($r, 'parent / guardian phone'), $tab, $n, 'Parent / guardian phone');
            $dupKey = $this->key($name).'|'.$class.'|'.strtolower($stream);
            if (isset($seen[$dupKey])) {
                $this->flag($tab, $n, 'Learner full name', "Same name, class and stream as row {$seen[$dupKey]}; kept both, check it is not a duplicate.");
            }
            $seen[$dupKey] ??= $n;

            $students[] = [$name, $class, $stream, $t['get']($r, 'parent / guardian full name'), $phone, $g, $dob ?? '', $t['get']($r, 'existing school id number')];
            $plan[] = [
                'row' => $n,
                'name' => $name,
                'class' => $class,
                'stream' => $stream,
                'gender' => $g,
                'date_of_birth' => $dob ?? '',
                'parent_name' => $t['get']($r, 'parent / guardian full name'),
                'parent_phone' => $phone,
                'parent_email' => $t['get']($r, 'parent / guardian email'),
                'school_student_id' => $t['get']($r, 'existing school id number'),
            ];
            $label = $class.($stream !== '' ? ' / '.$stream : '');
            $byClass[$label] = ($byClass[$label] ?? 0) + 1;
            $extra = [$t['get']($r, 'parent / guardian email'), $t['get']($r, 'relationship'), $t['get']($r, 'second parent / guardian name'), $this->phone($t['get']($r, 'second parent / guardian phone'), $tab, $n, 'Second parent / guardian phone')];
            if (implode('', $extra) !== '') {
                $guardian[] = array_merge([$name, $class, $stream], $extra);
            }
            $this->accept($tab);
        }
        ksort($byClass);

        return [$students, $guardian, $byClass, $plan];
    }

    /**
     * @return list<array{row: int, year: string, name: string, start: string, end: string}>
     */
    private function terms(?Worksheet $ws, array &$out): array
    {
        $plan = [];
        if ($ws === null) {
            return $plan;
        }
        $rows = $ws->toArray(null, true, false, false);
        $tab = 'Terms & Exams';
        $termHeader = $rows[0] ?? [];
        $cols = $this->headerMap($termHeader);
        $terms = [['Academic year', 'Term', 'Start date', 'End date', 'Notes']];
        $assessments = [['Assessment name', 'Classes it applies to', 'Out of (marks)', 'Counts on the report card?', 'Notes']];
        $mode = 'terms';
        $grading = [];
        $skipNext = false;
        foreach ($rows as $i => $row) {
            $n = $i + 1;
            if ($i < 2) {
                continue;
            }
            $first = $this->text($row[0] ?? '');
            if (str_starts_with(strtolower($first), 'assessment name')) {
                $mode = 'assess';
                $skipNext = true;

                continue;
            }
            if (str_contains(strtolower($first), 'grading')) {
                $mode = 'grading';
                $rest = trim(implode(' ', array_filter(array_map(fn ($c) => $this->text($c), array_slice($row, 1)))));
                if ($rest !== '') {
                    $grading[] = $rest;
                }

                continue;
            }
            if ($mode === 'grading') {
                $line = trim(implode(' ', array_filter(array_map(fn ($c) => $this->text($c), $row))));
                if ($line !== '') {
                    $grading[] = $line;
                }

                continue;
            }
            if ($first === '') {
                continue;
            }
            if ($mode === 'assess') {
                if ($skipNext) {
                    $skipNext = false;
                    if ($this->isGreyRow($ws, $n)) {
                        continue;
                    }
                }
                $this->read('Terms & Exams (assessments)');
                $assessments[] = [$first, $this->text($row[1] ?? ''), $this->text($row[2] ?? ''), $this->text($row[3] ?? ''), $this->text($row[4] ?? '')];
                $this->accept('Terms & Exams (assessments)');

                continue;
            }
            $this->read($tab);
            $start = $this->date($row[2] ?? '');
            $end = $this->date($row[3] ?? '');
            $term = $this->text($row[1] ?? '');
            $bad = false;
            if ($term === '') {
                $this->flag($tab, $n, 'Term', 'Missing required value in Term.');
                $bad = true;
            }
            if ($start === null || $end === null) {
                $this->flag($tab, $n, 'Start date / End date', 'Start or end date missing or not a date (expected day/month/year).');
                $bad = true;
            } elseif ($end < $start) {
                $this->flag($tab, $n, 'End date', 'End date is before the start date.');
                $bad = true;
            }
            if ($bad) {
                continue;
            }
            $terms[] = [$first, $term, $start, $end, $this->text($row[4] ?? '')];
            $plan[] = ['row' => $n, 'year' => $first, 'name' => $term, 'start' => $start, 'end' => $end];
            $this->accept($tab);
        }
        unset($cols);
        $out['noImporter']['terms.csv'] = $terms;
        if (count($assessments) > 1) {
            $out['pending']['assessments.csv'] = $assessments;
        }
        if ($grading !== []) {
            $out['pending']['grading.txt'] = [[implode("\n", $grading)]];
        }

        return $plan;
    }

    private function fees(?Worksheet $ws, array $classes, array &$out): void
    {
        $t = $this->table($ws);
        $tab = 'Fees';
        $csv = [['Fee name', 'Class', 'Amount', 'Currency', 'Duration', 'Compulsory', 'Notes']];
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            $n = $r['n'];
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $classRaw = $t['get']($r, 'class');
            $class = strtolower($classRaw) === 'all' ? 'All' : $this->canonicalClass($classRaw, $tab, $n);
            if ($class === null) {
                continue;
            }
            $amount = preg_replace('/[,\s]/', '', $t['get']($r, 'amount')) ?? '';
            if (! is_numeric($amount) || (float) $amount < 0) {
                $this->flag($tab, $n, 'Amount', "\"{$t['get']($r, 'amount')}\" is not an amount.");

                continue;
            }
            $duration = ['termly' => 'Termly', 'one-off' => 'One-off', 'one off' => 'One-off', 'yearly' => 'Yearly'][strtolower($t['get']($r, 'duration (termly, one-off, yearly)') ?: $t['get']($r, 'duration'))] ?? null;
            if ($duration === null) {
                $this->flag($tab, $n, 'Duration', 'Duration must be Termly, One-off or Yearly.');

                continue;
            }
            $comp = ['yes' => 'Yes', 'no' => 'No'][strtolower($t['get']($r, 'compulsory?'))] ?? '';
            $csv[] = [$t['get']($r, 'fee name'), $class, $amount, strtoupper($t['get']($r, 'currency')), $duration, $comp, $t['get']($r, 'notes')];
            $this->accept($tab);
        }
        $out['noImporter']['fees.csv'] = $csv;
    }

    private function adminContacts(?Worksheet $ws, array &$out): void
    {
        $t = $this->table($ws, fn (array $row, int $n) => $n === 14 || str_starts_with(strtolower($this->text($row[0] ?? '')), 'school whatsapp'));
        $tab = 'Admin & Contacts';
        $csv = [['Role', 'Full name', 'Email', 'Phone', 'WhatsApp on this number', 'Notes']];
        foreach ($t['rows'] ?? [] as $r) {
            $this->read($tab);
            if (! $this->required($t, $r, $tab)) {
                continue;
            }
            $csv[] = [$t['get']($r, 'role'), $t['get']($r, 'full name'), $t['get']($r, 'email'), $this->phone($t['get']($r, 'phone'), $tab, $r['n'], 'Phone'), $t['get']($r, 'whatsapp on this number?'), $t['get']($r, 'notes')];
            $this->accept($tab);
        }
        if ($ws !== null) {
            $wa = $this->text($ws->getCell('B14')->getValue());
            if ($wa !== '') {
                $csv[] = ['School WhatsApp number', '', '', $this->phone($wa, $tab, 14, 'School WhatsApp number'), 'Yes', ''];
            }
        }
        $out['noImporter']['admin-contacts.csv'] = $csv;
    }

    // --------------------------------------------------------------- helpers

    /** @return array{get: \Closure, raw: \Closure, rows: list<array>, required: list<string>}|null */
    private function table(?Worksheet $ws, ?\Closure $skip = null): ?array
    {
        if ($ws === null) {
            return null;
        }
        $all = $ws->toArray(null, true, false, false);
        $header = $all[0] ?? [];
        $map = $this->headerMap($header);
        $required = [];
        foreach ($header as $h) {
            if (str_ends_with(trim((string) $h), ' *')) {
                $required[] = $this->headerKey($h);
            }
        }
        $rows = [];
        foreach ($all as $i => $row) {
            if ($i < 2) {
                continue; // row 1 header, row 2 grey example
            }
            $n = $i + 1;
            if ($this->text($row[0] ?? '') === '' || ($skip && $skip($row, $n))) {
                continue;
            }
            $rows[] = ['n' => $n, 'cells' => $row];
        }
        $get = fn (array $r, string $key): string => isset($map[$key]) ? $this->text($r['cells'][$map[$key]] ?? '') : '';
        $raw = fn (array $r, string $key) => isset($map[$key]) ? ($r['cells'][$map[$key]] ?? '') : '';

        return ['get' => $get, 'raw' => $raw, 'rows' => $rows, 'required' => $required, 'map' => $map, 'tab' => $ws->getTitle()];
    }

    private function required(?array $t, array $r, string $tab): bool
    {
        $ok = true;
        foreach ($t['required'] as $key) {
            if (($t['get'])($r, $key) === '') {
                $this->flag($tab, $r['n'], ucfirst($key), 'Missing required value in '.ucfirst($key).'.');
                $ok = false;
            }
        }

        return $ok;
    }

    private function headerMap(array $header): array
    {
        $map = [];
        foreach ($header as $i => $h) {
            $k = $this->headerKey($h);
            if ($k !== '' && ! isset($map[$k])) {
                $map[$k] = $i;
            }
        }

        return $map;
    }

    private function headerKey(mixed $h): string
    {
        $s = strtolower(trim((string) $h));
        $s = preg_replace('/\s*\*$/', '', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    private function sheet(Spreadsheet $book, string $title): ?Worksheet
    {
        foreach ($book->getAllSheets() as $s) {
            if (strtolower(trim($s->getTitle())) === strtolower($title)) {
                return $s;
            }
        }
        $this->flag($title, null, 'Tab', "The tab \"{$title}\" was not found in the workbook.");

        return null;
    }

    private function sheetStartingWith(Spreadsheet $book, string $prefix): ?Worksheet
    {
        foreach ($book->getAllSheets() as $s) {
            if (str_starts_with(strtolower(trim($s->getTitle())), strtolower($prefix))) {
                return $s;
            }
        }
        $this->flag('Learners', null, 'Tab', 'The tab "All Learners (for import)" was not found.');

        return null;
    }

    /** @return list<string> class tabs that have at least one row below the example row. */
    private function classTabsWithRows(Spreadsheet $book): array
    {
        $found = [];
        foreach ($book->getAllSheets() as $s) {
            $canonical = $this->resolveClass($s->getTitle());
            if ($canonical === null) {
                continue;
            }
            foreach (array_slice($s->toArray(null, true, false, false), 2) as $row) {
                if ($this->text($row[0] ?? '') !== '') {
                    $found[] = $canonical;
                    break;
                }
            }
        }

        return $found;
    }

    /** @return array<string,string> */
    private function formPairs(?Worksheet $ws): array
    {
        $pairs = [];
        foreach ($ws?->toArray(null, true, false, false) ?? [] as $row) {
            $k = strtolower($this->text($row[0] ?? ''));
            if ($k !== '') {
                $pairs[$k] = $this->text($row[1] ?? '');
            }
        }

        return $pairs;
    }

    private function pairValue(array $pairs, string $needle): ?string
    {
        foreach ($pairs as $k => $v) {
            if (str_contains($k, $needle) && $v !== '') {
                return $v;
            }
        }

        return null;
    }

    private function resolveClass(string $raw): ?string
    {
        $s = strtolower(trim(preg_replace('/\s+/', ' ', $raw) ?? $raw));
        foreach (self::CANONICAL_CLASSES as $c) {
            if (strtolower($c) === $s) {
                return $c;
            }
        }
        $s = str_replace('.', '', $s);
        if (preg_match('/^(?:p|primary) ?([1-7])$/', $s, $m)) {
            return 'Primary '.self::WORDS[(int) $m[1]];
        }
        if (preg_match('/^(?:s|senior) ?([1-6])$/', $s, $m)) {
            return 'Senior '.self::WORDS[(int) $m[1]];
        }

        return ['baby' => 'Baby Class', 'middle' => 'Middle Class', 'top' => 'Top Class'][$s] ?? null;
    }

    private function canonicalClass(string $raw, string $tab, int $row): ?string
    {
        $c = $this->resolveClass($raw);
        if ($c === null) {
            $this->flag($tab, $row, 'Class', "\"{$raw}\" is not a KlassApp class name (expected e.g. Baby Class, Primary Three, Senior One).");

            return null;
        }
        if ($c !== trim($raw)) {
            $this->flag($tab, $row, 'Class', "\"{$raw}\" written as {$c}.", 'info');
        }

        return $c;
    }

    private function phone(string $raw, string $tab, int $row, string $field): string
    {
        // One phone rule for the whole app: the same converter phones:to-e164 uses (#1286).
        $result = (new PhoneE164Converter)->convert($raw, $this->country);
        if ($result['status'] === PhoneE164Converter::UNPARSEABLE) {
            $this->flag($tab, $row, $field, '"'.trim($raw).'" not converted: '.$result['reason'].'.');

            return '';
        }

        return (string) ($result['value'] ?? '');
    }

    private function date(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v) && (float) $v > 1 && (float) $v < 80000) {
            return XlDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        $s = trim((string) $v);
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]) : null;
        }
        if (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        }
        $ts = strtotime($s);

        return $ts !== false && preg_match('/[a-z]{3}/i', $s) ? date('Y-m-d', $ts) : null;
    }

    private function isGreyRow(Worksheet $ws, int $row): bool
    {
        $fill = $ws->getStyle('A'.$row)->getFill();

        return $fill->getFillType() !== 'none' && ! in_array(strtoupper((string) $fill->getStartColor()->getARGB()), ['FFFFFFFF', ''], true);
    }

    private function text(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_float($v) && floor($v) === $v) {
            return (string) (int) $v;
        }
        $s = trim((string) $v);

        return str_starts_with($s, '=') ? '' : $s;
    }

    private function key(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? $name));
    }

    private function read(string $tab): void
    {
        $this->counts[$tab]['read'] = ($this->counts[$tab]['read'] ?? 0) + 1;
        $this->counts[$tab]['accepted'] ??= 0;
    }

    private function accept(string $tab): void
    {
        $this->counts[$tab]['accepted'] = ($this->counts[$tab]['accepted'] ?? 0) + 1;
    }

    private function flag(string $tab, ?int $row, string $field, string $message, string $severity = 'error'): void
    {
        $this->flags[] = ['tab' => $tab, 'row' => $row, 'field' => $field, 'message' => $message, 'severity' => $severity];
    }
}
