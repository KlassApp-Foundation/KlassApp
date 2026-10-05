<?php

namespace App\Services\MarksImport;

use App\Events\MarksUpdated;
use App\Helpers\GradingHelper;
use App\Imports\MarksSheetReader;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\ExamAuthorization;
use App\Services\GradingSystemService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Marks import from a spreadsheet, with no UI attached.
 *
 *   $plan   = $service->preview($exam, $actor, $rows|$filePath);   // pure, saves nothing
 *   $result = $service->commit($plan, $actor, overwrite: false);   // idempotent
 *
 * One exam = one class + one subject + one exam type, so a file carries one
 * marks column. Authorisation matches the marks-entry screens: a school admin
 * for any exam in their school, otherwise ExamAuthorization::canActOnExam
 * (exam owner or class teacher). Students must be enrolled in the exam's class.
 *
 * Safety rules (see docs/proposals/2026-10-05-marks-import-ui.md):
 *  - every input row gets exactly one outcome; nothing is dropped silently;
 *  - matching is by registration (admission) number only, never by name
 *    (docs/proposals/2026-10-05-marks-import-ui.md, standing rules 4 and 18);
 *  - an existing mark that differs is only changed when overwrite is confirmed;
 *  - the plan is re-validated against the database at commit time.
 */
class MarksImportService
{
    public const MAX_ROWS = 5000;

    public const MIN_MARK = 0.0;

    public const MAX_MARK = 100.0;

    /** Stamped into every template's Exam info sheet; bump when the template shape changes. */
    public const TEMPLATE_VERSION = 'marks-import-v1';

    private const ADMIN_USERGROUPS = [1, 3];

    private const TEACHER_USERGROUP = 5;

    private const STUDENT_USERGROUP = 6;

    private const PLAN_TTL_SECONDS = 3600;

    /** Reason codes for skipped rows, with the wording shown to users. */
    public const REASONS = [
        'blank_mark' => 'No mark entered',
        'not_a_number' => 'Mark is not a number',
        'out_of_range' => 'Mark is outside 0 to 100',
        'no_grade_band' => 'No grading band covers this mark',
        'unknown_student' => 'Student not found',
        'not_in_class' => 'Not a student in this class',
        'no_identifier' => 'No KLS number on this row (students are matched by KLS number, not by name)',
        'duplicate_in_file' => 'Student appears more than once in the file',
        'duplicate_existing' => 'More than one saved mark exists for this student; fix it in marks entry',
        'overwrite_not_confirmed' => 'A different mark is already saved and overwriting was not confirmed',
    ];

    private const ID_HEADERS = ['admissionno', 'admissionnumber', 'admno', 'admission', 'registrationnumber', 'regno', 'klsnumber', 'klsno', 'kls', 'studentid', 'id'];

    private const NAME_HEADERS = ['student', 'studentname', 'name', 'learner', 'learnername', 'pupil', 'pupilname'];

    private const MARK_HEADERS = ['marks', 'mark', 'score', 'scores'];

    public function __construct(
        private readonly ExamAuthorization $examAuthorization,
        private readonly GradingSystemService $grading,
    ) {
    }

    // ───────────────────────────── access ─────────────────────────────

    public function canImport(User $actor, Exam $exam): bool
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            return false;
        }

        if (in_array((int) $actor->usergroup_id, self::ADMIN_USERGROUPS, true)) {
            return true;
        }

        return (int) $actor->usergroup_id === self::TEACHER_USERGROUP
            && $this->examAuthorization->canActOnExam($actor, $exam);
    }

    public function assertCanImport(User $actor, Exam $exam): void
    {
        if (! $this->canImport($actor, $exam)) {
            throw new HttpException(403, 'You are not authorized to import marks for this exam.');
        }
    }

    // ───────────────────────────── template ─────────────────────────────

    /**
     * Mark-sheet headings for every template download (teacher and admin): the first
     * column is the student's KLS number; the mark column names its range.
     *
     * @return list<string>
     */
    public function templateHeadings(): array
    {
        return ['KLS number', 'student_name', 'Mark (out of '.(int) self::MAX_MARK.')'];
    }

    /**
     * The template key: the exam id plus a checksum over the exam's identity. Written
     * into the Exam info sheet and re-computed at import time to catch wrong-exam files.
     */
    public function templateKey(Exam $exam): string
    {
        $payload = implode('|', [
            self::TEMPLATE_VERSION,
            'exam='.(int) $exam->id,
            'school='.(int) $exam->school_id,
            'section='.(int) $exam->section_id,
            'subject='.(int) $exam->subject_id,
            'term='.(int) $exam->academic_term_id,
        ]);

        return $exam->id.'-'.substr(hash('sha256', $payload), 0, 10);
    }

    /**
     * Exam info sheet rows (label, value) that ride along as the template's second sheet.
     *
     * @return list<array{0:string,1:string}>
     */
    public function templateInfoRows(Exam $exam): array
    {
        $exam->loadMissing('school', 'subject', 'section', 'standard', 'examType', 'academicTerm');

        return [
            ['School', (string) ($exam->school?->name ?? '')],
            ['Class and stream', $this->examClassLabel($exam)],
            ['Subject', (string) ($exam->subject?->name ?? '')],
            ['Exam', (string) ($exam->examType?->name ?? '')],
            ['Term', (string) ($exam->academicTerm?->name ?? '')],
            ['Maximum marks', (string) (int) self::MAX_MARK],
            ['Generated', now()->format('Y-m-d H:i')],
            ['Template version', self::TEMPLATE_VERSION],
            ['Template key', $this->templateKey($exam)],
        ];
    }

    private function examClassLabel(Exam $exam): string
    {
        return (string) ($exam->section?->name ?? $exam->standard?->name ?? '');
    }

    /** Human label for refusal messages: "Grade 4 Mathematics". */
    private function examLabel(Exam $exam): string
    {
        $label = trim($this->examClassLabel($exam).' '.trim((string) ($exam->subject?->name ?? '')));

        return $label !== '' ? $label : 'this exam';
    }

    /**
     * One row per enrolled student, mark cell empty: the same roster and row shape as
     * MarksController::downloadTemplate, so a template from either route imports the same way.
     *
     * @return list<array{0:?string,1:string,2:null}>
     */
    public function templateRows(Exam $exam, User $actor): array
    {
        $this->assertCanImport($actor, $exam);

        return $this->roster($exam)->map(fn (User $s) => [
            $s->registration_number,
            (string) ($s->displayName ?: $s->name),
            null,
        ])->values()->all();
    }

    // ───────────────────────────── reading ─────────────────────────────

    /**
     * Read every sheet of an xlsx, xls or csv file into raw rows (sheet order kept).
     *
     * @return list<list<list<mixed>>>
     */
    public function readSheets(string $path, ?string $extension = null): array
    {
        $type = match (strtolower((string) $extension)) {
            'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            default => null,
        };

        $sheets = Excel::toArray(new MarksSheetReader, $path, null, $type);

        return array_values(array_map(fn ($sheet) => array_values((array) $sheet), $sheets));
    }

    /**
     * Read the first sheet into raw rows (callers that know they have a single sheet).
     *
     * @return list<list<mixed>>
     */
    public function readRows(string $path, ?string $extension = null): array
    {
        return $this->readSheets($path, $extension)[0] ?? [];
    }

    // ───────────────────────────── preview ─────────────────────────────

    /**
     * Validate and describe what would be saved. Saves nothing.
     *
     * @param  list<list<mixed>>|string  $source  Raw rows (header row first) or a file path.
     */
    public function preview(Exam $exam, User $actor, array|string $source, ?string $fileName = null, ?string $fileSha256 = null): MarksImportPlan
    {
        $this->assertCanImport($actor, $exam);

        if (is_array($source)) {
            return $this->previewSheets($exam, $actor, [$source], $fileName, $fileSha256);
        }

        $sheets = $this->readSheets($source, pathinfo($fileName ?? $source, PATHINFO_EXTENSION));
        $fileSha256 ??= is_file($source) ? hash_file('sha256', $source) : hash('sha256', $source);

        return $this->previewSheets($exam, $actor, $sheets, $fileName ?? 'rows', $fileSha256);
    }

    /**
     * Validate and describe what would be saved, from raw sheets (marks sheet first,
     * optional Exam info sheet). Saves nothing.
     *
     * @param  list<list<list<mixed>>>  $sheets
     */
    public function previewSheets(Exam $exam, User $actor, array $sheets, ?string $fileName = null, ?string $fileSha256 = null): MarksImportPlan
    {
        $this->assertCanImport($actor, $exam);

        $marksSheet = $this->findMarksSheet($sheets);
        [$input, $blockers] = $this->normaliseInput($marksSheet ?? []);
        [, $verifyBlockers, $warnings] = $this->verifyExamInfoSheet($sheets, $exam);

        return $this->plan(
            $exam, $input, array_merge($blockers, $verifyBlockers),
            $fileName ?? 'rows', $fileSha256 ?? hash('sha256', json_encode($sheets)), $warnings,
        );
    }

    // ───────────────────────────── commit ─────────────────────────────

    /**
     * Save a previewed plan. Idempotent: the plan is rebuilt from its input rows
     * against the current database, so a second run of the same file finds the
     * marks already saved and changes nothing.
     *
     * @throws MarksImportBlocked
     */
    public function commit(MarksImportPlan $plan, User $actor, bool $overwrite = false, ?string $reason = null, ?string $requestId = null): MarksImportResult
    {
        $exam = Exam::query()->where('school_id', $plan->schoolId)->findOrFail($plan->examId);
        $this->assertCanImport($actor, $exam);

        if ($plan->isBlocked()) {
            $blockers = $plan->blockers;
            throw new MarksImportBlocked((string) array_key_first($blockers), (string) reset($blockers));
        }

        $lock = Cache::lock('marks-import:exam:'.$exam->id, 60);
        if (! $lock->get()) {
            throw new MarksImportBlocked('busy', 'Another import for this exam is running. Wait a moment and check the result.');
        }

        try {
            $fresh = $this->plan($exam, $plan->input, [], $plan->fileName, $plan->fileSha256);
            if ($fresh->isBlocked()) {
                $blockers = $fresh->blockers;

                throw new MarksImportBlocked((string) array_key_first($blockers), (string) reset($blockers));
            }

            $reason = $this->validatedReason($exam, $reason);
            $result = DB::transaction(fn () => $this->apply($exam, $actor, $fresh, $overwrite));

            $this->audit($exam, $actor, $plan, $result, $reason, $overwrite, $requestId);
            $this->notify($exam, $actor, $result, $reason);

            return $result;
        } finally {
            $lock->release();
        }
    }

    // ───────────────────────────── hand-off between requests ─────────────────────────────

    public function stashPlan(MarksImportPlan $plan, User $actor): string
    {
        $token = Str::random(40);
        Cache::put($this->cacheKey('plan', $token), ['actor' => $actor->id, 'plan' => $plan->toArray()], self::PLAN_TTL_SECONDS);

        return $token;
    }

    public function retrievePlan(string $token, User $actor, Exam $exam): ?MarksImportPlan
    {
        $data = Cache::get($this->cacheKey('plan', $token));
        if (! is_array($data) || (int) $data['actor'] !== (int) $actor->id || (int) $data['plan']['exam_id'] !== (int) $exam->id) {
            return null;
        }

        return MarksImportPlan::fromArray($data['plan']);
    }

    public function stashResult(MarksImportResult $result, User $actor, string $planToken): void
    {
        Cache::put($this->cacheKey('result', $planToken), ['actor' => $actor->id, 'result' => $result->toArray()], self::PLAN_TTL_SECONDS);
    }

    public function retrieveResult(string $token, User $actor, Exam $exam): ?MarksImportResult
    {
        $data = Cache::get($this->cacheKey('result', $token));
        if (! is_array($data) || (int) $data['actor'] !== (int) $actor->id || (int) $data['result']['exam_id'] !== (int) $exam->id) {
            return null;
        }

        return MarksImportResult::fromArray($data['result']);
    }

    // ───────────────────────────── internals ─────────────────────────────

    /**
     * Find the header row and turn the sheet into normalised input rows.
     *
     * @param  list<list<mixed>>  $raw
     * @return array{0:list<array{row:int,identifier:?string,name:?string,mark:?string}>,1:array<string,string>}
     */
    /**
     * First sheet that carries a recognisable header row (KLS-number column + mark column).
     *
     * @param  list<list<list<mixed>>>  $sheets
     * @return ?list<list<mixed>>
     */
    private function findMarksSheet(array $sheets): ?array
    {
        foreach ($sheets as $sheet) {
            if ($this->detectHeader($sheet) !== null) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * @param  list<list<mixed>>  $raw
     * @return ?array{0:int,1:array{id:?int,name:?int,mark:?int}}
     */
    private function detectHeader(array $raw): ?array
    {
        foreach (array_slice($raw, 0, 10, true) as $i => $cells) {
            $map = ['id' => null, 'name' => null, 'mark' => null];
            foreach (array_values((array) $cells) as $col => $cell) {
                $key = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $cell)));
                if ($key === '') {
                    continue;
                }
                if ($map['id'] === null && in_array($key, self::ID_HEADERS, true)) {
                    $map['id'] = $col;
                } elseif ($map['name'] === null && in_array($key, self::NAME_HEADERS, true)) {
                    $map['name'] = $col;
                } elseif ($map['mark'] === null && (in_array($key, self::MARK_HEADERS, true) || str_starts_with($key, 'mark'))) {
                    $map['mark'] = $col;
                }
            }
            if ($map['mark'] !== null && $map['id'] !== null) {
                return [$i, $map];
            }
        }

        return null;
    }

    private function normaliseInput(array $raw): array
    {
        $header = $this->detectHeader($raw);

        if ($header === null) {
            return [[], ['columns' => 'The file needs a "KLS number" column and a "Mark" column. Download the template and use it as the starting point.']];
        }

        [$headerIndex, $map] = $header;
        $input = [];
        foreach ($raw as $i => $cells) {
            if ($i <= $headerIndex) {
                continue;
            }
            $cells = array_values((array) $cells);
            $cell = fn (?int $col) => $col === null ? null : trim((string) ($cells[$col] ?? ''));
            $identifier = $cell($map['id']);
            $name = $cell($map['name']);
            $mark = $cell($map['mark']);
            if (($identifier ?? '') === '' && ($name ?? '') === '' && ($mark ?? '') === '') {
                continue; // wholly empty spreadsheet row, nothing to report
            }
            $input[] = ['row' => $i + 1, 'identifier' => $identifier === '' ? null : $identifier, 'name' => $name === '' ? null : $name, 'mark' => $mark === '' ? null : $mark];
        }

        $blockers = [];
        if ($input === []) {
            $blockers['empty'] = 'The file has no rows below the header.';
        } elseif (count($input) > self::MAX_ROWS) {
            $blockers['too_many_rows'] = 'The file has more than '.self::MAX_ROWS.' rows. Split it and import in parts.';
            $input = array_slice($input, 0, self::MAX_ROWS);
        }

        return [$input, $blockers];
    }

    /**
     * Check the Exam info sheet (when present): the key and the class/subject/exam
     * fields must match the exam being imported into. A file without the sheet is
     * allowed but warned about; a mismatch is refused before any preview is shown.
     *
     * @param  list<list<list<mixed>>>  $sheets
     * @return array{0:?array<string,string>,1:array<string,string>,2:list<string>} [info, blockers, warnings]
     */
    private function verifyExamInfoSheet(array $sheets, Exam $exam): array
    {
        $info = null;
        foreach ($sheets as $sheet) {
            $map = $this->infoMap($sheet);
            if ($map !== null && array_key_exists('templatekey', $map)) {
                $info = $map;
                break;
            }
        }

        $exam->loadMissing('subject', 'section', 'standard', 'examType');

        if ($info === null) {
            return [null, [], ['This file has no Exam info sheet, so the exam could not be checked. Make sure it was made for '.$this->examLabel($exam).'.']];
        }

        $fileLabel = trim(((string) ($info['classandstream'] ?? '')).' '.((string) ($info['subject'] ?? '')));
        $fileLabel = $fileLabel !== '' ? $fileLabel : 'a different exam';

        $ok = trim((string) ($info['templatekey'] ?? '')) === $this->templateKey($exam);
        if ($ok) {
            $expected = [
                'classandstream' => $this->examClassLabel($exam),
                'subject' => (string) ($exam->subject?->name ?? ''),
                'exam' => (string) ($exam->examType?->name ?? ''),
            ];
            foreach ($expected as $label => $value) {
                $have = trim((string) ($info[$label] ?? ''));
                if ($have !== '' && ! $this->sameText($have, $value)) {
                    $ok = false;
                    break;
                }
            }
        }

        if (! $ok) {
            $target = $this->examLabel($exam);
            if ($this->sameText($fileLabel, $target)) {
                $fileExam = trim((string) ($info['exam'] ?? ''));
                $targetExam = trim((string) ($exam->examType?->name ?? ''));
                if ($fileExam !== '' && $targetExam !== '' && ! $this->sameText($fileExam, $targetExam)) {
                    $fileLabel .= ' ('.$fileExam.')';
                    $target .= ' ('.$targetExam.')';
                }
            }

            return [$info, ['exam_mismatch' => "This file is for {$fileLabel}; you are importing into {$target}."], []];
        }

        return [$info, [], []];
    }

    /**
     * @param  list<mixed>  $sheet
     * @return ?array<string,string>
     */
    private function infoMap(array $sheet): ?array
    {
        $map = [];
        foreach (array_slice($sheet, 0, 30) as $cells) {
            $cells = array_values((array) $cells);
            $label = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) ($cells[0] ?? ''))));
            if ($label === '') {
                continue;
            }
            if (! array_key_exists($label, $map)) {
                $map[$label] = trim((string) ($cells[1] ?? ''));
            }
        }

        return $map === [] ? null : $map;
    }

    private function sameText(string $a, string $b): bool
    {
        $normalise = fn (string $s) => strtolower((string) preg_replace('/\s+/', ' ', trim($s)));

        return $normalise($a) === $normalise($b);
    }

    /**
     * @param  list<array{row:int,identifier:?string,name:?string,mark:?string}>  $input
     * @param  array<string,string>  $blockers
     * @param  list<string>  $warnings
     */
    private function plan(Exam $exam, array $input, array $blockers, string $fileName, string $fileSha256, array $warnings = []): MarksImportPlan
    {
        $exam->loadMissing('standard');

        if ($exam->standard && GradingHelper::levelTypeForStandard($exam->standard) === 'nursery') {
            $blockers['nursery'] = 'This class is assessed with domain ratings, not marks, so it cannot be imported from a marks spreadsheet.';
        }

        $submission = ExamMarksSubmission::query()
            ->where('exam_id', $exam->id)->where('class_id', $exam->section_id)->where('subject_id', $exam->subject_id)->first();
        if ($submission && $submission->isLocked()) {
            $deadline = $submission->deadline ? ' (deadline '.$submission->deadline->format('j M Y H:i').')' : '';
            $blockers['locked'] = 'Marks for this exam are locked'.$deadline.'. Ask a school admin to reopen them.';
        }

        $roster = $this->roster($exam);
        $byAdmission = $roster->filter(fn (User $s) => ($s->registration_number ?? '') !== '')
            ->keyBy(fn (User $s) => $this->key($s->registration_number));
        $existing = $this->existingMarks($exam);

        // First pass: resolve each row to a student (or a skip reason).
        $resolved = [];
        foreach ($input as $in) {
            $resolved[] = $this->resolveRow($exam, $in, $byAdmission, $existing);
        }

        // Duplicate students in the file: identical marks keep the first, conflicting marks skip all.
        $groups = [];
        foreach ($resolved as $i => $r) {
            if ($r['outcome'] !== 'skipped' || false) {
                $groups[$r['student_id']][] = $i;
            }
        }
        foreach ($groups as $indices) {
            if (count($indices) < 2) {
                continue;
            }
            $marks = array_unique(array_map(fn ($i) => $resolved[$i]['mark'], $indices));
            foreach ($indices as $n => $i) {
                if (count($marks) > 1 || $n > 0) {
                    $resolved[$i] = $this->skip($resolved[$i], 'duplicate_in_file');
                }
            }
        }

        return new MarksImportPlan(
            $exam->id, (int) $exam->school_id, $fileName, $fileSha256, $input, $resolved, $blockers,
            $exam->status === 'submitted', $warnings,
        );
    }

    /**
     * @param  array{row:int,identifier:?string,name:?string,mark:?string}  $in
     * @return array<string,mixed>
     */
    private function resolveRow(Exam $exam, array $in, $byAdmission, $existing): array
    {
        $row = [
            'row' => $in['row'], 'identifier' => $in['identifier'], 'name' => $in['name'],
            'student_id' => null, 'student_name' => null, 'raw_mark' => $in['mark'], 'mark' => null, 'existing' => null,
            'outcome' => 'skipped', 'reason' => null, 'message' => null,
        ];

        // 1. Who is it?
        $student = null;
        if ($in['identifier'] !== null) {
            $student = $byAdmission->get($this->key($in['identifier']));
            if (! $student) {
                $inSchool = User::query()->where('school_id', $exam->school_id)->where('usergroup_id', self::STUDENT_USERGROUP)
                    ->where('registration_number', $in['identifier'])->exists();

                return $this->skip($row, $inSchool ? 'not_in_class' : 'unknown_student');
            }
        } else {
            return $this->skip($row, 'no_identifier');
        }
        $row['student_id'] = $student->id;
        $row['student_name'] = $student->name;

        // 2. What is the mark?
        if ($in['mark'] === null) {
            return $this->skip($row, 'blank_mark');
        }
        $text = preg_match('/^\d+,\d{1,2}$/', $in['mark']) ? str_replace(',', '.', $in['mark']) : $in['mark'];
        if (! is_numeric($text) || ! is_finite((float) $text)) {
            return $this->skip($row, 'not_a_number');
        }
        $mark = round((float) $text, 2);
        if ($mark < self::MIN_MARK || $mark > self::MAX_MARK) {
            return $this->skip($row, 'out_of_range');
        }
        $row['mark'] = $this->formatMark($mark);

        if ($this->grading->grade((int) $mark, (int) $exam->school_id, $exam) === null) {
            return $this->skip($row, 'no_grade_band');
        }

        // 3. Is there already a saved mark?
        $saved = $existing->get($student->id);
        if ($saved && $saved->count() > 1) {
            return $this->skip($row, 'duplicate_existing');
        }
        if ($saved) {
            $old = (float) $saved->first()->marks;
            $row['existing'] = $this->formatMark($old);
            $row['outcome'] = abs($old - $mark) < 0.005 ? 'unchanged' : 'update';
        } else {
            $row['outcome'] = 'new';
        }

        return $row;
    }

    /** @param  array<string,mixed>  $row */
    private function skip(array $row, string $reason): array
    {
        $row['outcome'] = 'skipped';
        $row['reason'] = $reason;
        $row['message'] = self::REASONS[$reason];

        return $row;
    }

    private function apply(Exam $exam, User $actor, MarksImportPlan $plan, bool $overwrite): MarksImportResult
    {
        // Re-read the saved marks under a lock so a concurrent entry-screen save cannot slip between plan and write.
        $existing = Marks::query()->where('exam_id', $exam->id)->where('school_id', $exam->school_id)
            ->where('subject_id', $exam->subject_id)->lockForUpdate()->get()->groupBy('student_id');

        $saved = $updated = $unchanged = $skipped = 0;
        $rows = [];

        foreach ($plan->rows as $row) {
            switch ($row['outcome']) {
                case 'new':
                    if ($existing->has($row['student_id'])) { // appeared since planning: do not duplicate
                        $rows[] = $this->skip($row, 'duplicate_existing');
                        $skipped++;
                        break;
                    }
                    Marks::create($this->attributes($exam, $actor, (int) $row['student_id'], (float) $row['mark']));
                    $rows[] = ['outcome' => 'saved'] + $row;
                    $saved++;
                    break;

                case 'update':
                    if (! $overwrite) {
                        $rows[] = $this->skip($row, 'overwrite_not_confirmed');
                        $skipped++;
                        break;
                    }
                    $current = $existing->get($row['student_id'])?->first();
                    if (! $current || $existing->get($row['student_id'])->count() > 1) {
                        $rows[] = $this->skip($row, 'duplicate_existing');
                        $skipped++;
                        break;
                    }
                    $current->update($this->attributes($exam, $actor, (int) $row['student_id'], (float) $row['mark']));
                    $rows[] = ['outcome' => 'updated'] + $row;
                    $updated++;
                    break;

                case 'unchanged':
                    $rows[] = $row;
                    $unchanged++;
                    break;

                default:
                    $rows[] = $row;
                    $skipped++;
            }
        }

        if (($saved + $updated) > 0 && $exam->status === 'undone') {
            $exam->status = 'done'; // an import never advances an exam to "submitted"
            $exam->save();
        }

        return new MarksImportResult($exam->id, $saved, $updated, $unchanged, $skipped, $rows);
    }

    /** @return array<string,mixed> */
    private function attributes(Exam $exam, User $actor, int $studentId, float $mark): array
    {
        return [
            'student_id' => $studentId,
            'exam_id' => $exam->id,
            'school_id' => $exam->school_id,
            'subject_id' => $exam->subject_id,
            'teacher_id' => $exam->teacher_id ?: $actor->id,
            'section_id' => $exam->section_id,
            'marks' => $mark,
            'grade' => $this->grading->grade((int) $mark, (int) $exam->school_id, $exam),
        ];
    }

    private function validatedReason(Exam $exam, ?string $reason): ?string
    {
        $reason = trim((string) $reason);
        if ($exam->status !== 'submitted') {
            return $reason === '' ? null : mb_substr($reason, 0, 500);
        }
        if (mb_strlen($reason) < 10) {
            throw new MarksImportBlocked('reason_required', 'These marks were already submitted. Give a reason of at least 10 characters for correcting them.');
        }
        if (mb_strlen($reason) > 500) {
            throw new MarksImportBlocked('reason_required', 'Keep the correction reason under 500 characters.');
        }

        return $reason;
    }

    private function audit(Exam $exam, User $actor, MarksImportPlan $plan, MarksImportResult $result, ?string $reason, bool $overwrite, ?string $requestId): void
    {
        activity()
            ->performedOn($exam)
            ->causedBy($actor)
            ->withProperties([
                'school_id' => (int) $exam->school_id,
                'exam_id' => (int) $exam->id,
                'section_id' => (int) $exam->section_id,
                'subject_id' => (int) $exam->subject_id,
                'actor_id' => (int) $actor->id,
                'source' => 'spreadsheet_import',
                'file_name' => $plan->fileName,
                'file_sha256' => $plan->fileSha256,
                'rows_in_file' => count($plan->input),
                'saved' => $result->saved,
                'updated' => $result->updated,
                'unchanged' => $result->unchanged,
                'skipped' => $result->skipped,
                'skipped_reasons' => $result->skippedByReason(),
                'overwrite_confirmed' => $overwrite,
                'is_correction' => $reason !== null,
                'correction_reason' => $reason,
                'request_id' => $requestId ?? (string) Str::uuid(),
            ])
            ->useLog('marks')
            ->log('marks.imported');
    }

    private function notify(Exam $exam, User $actor, MarksImportResult $result, ?string $reason): void
    {
        $changed = $result->saved + $result->updated;
        if ($changed === 0) {
            return;
        }
        try {
            $afterReopen = ExamMarksSubmission::where('exam_id', $exam->id)->where('class_id', $exam->section_id)
                ->where('subject_id', $exam->subject_id)->where('status', 'reopened')->exists();
            event(new MarksUpdated($exam, $actor, $reason, $afterReopen, $changed));
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch MarksUpdated after import: {$e->getMessage()}");
        }
    }

    /** Students enrolled in the exam's class: the same rule as the entry screen and the #986 template (usergroup 6, same school, standard link of this standard and section). */
    private function roster(Exam $exam)
    {
        return User::query()
            ->where('usergroup_id', self::STUDENT_USERGROUP)
            ->where('school_id', $exam->school_id)
            ->whereHas('studentAcademic', fn ($q) => $q->whereHas('standardLink', fn ($q2) => $q2
                ->where('standard_id', $exam->standard_id)
                ->where('section_id', $exam->section_id)))
            ->orderBy('name')
            ->get();
    }

    private function existingMarks(Exam $exam)
    {
        return Marks::query()->where('exam_id', $exam->id)->where('school_id', $exam->school_id)
            ->where('subject_id', $exam->subject_id)->get()->groupBy('student_id');
    }

    private function key(string $value): string
    {
        return strtolower(preg_replace('/\s+/', '', trim($value)));
    }

    private function formatMark(float $mark): string
    {
        return rtrim(rtrim(number_format($mark, 2, '.', ''), '0'), '.');
    }

    private function cacheKey(string $kind, string $token): string
    {
        return "marks-import:{$kind}:{$token}";
    }
}
