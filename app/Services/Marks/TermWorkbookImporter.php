<?php

namespace App\Services\Marks;

use App\Exceptions\MarksLockedException;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSet;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\ExamAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Imports one term's marks workbook as drafts.
 *
 * A class tab must already use that school's class name. Subjects and exam
 * types that are not already in the school are reported and not created.
 * Setup's "on the report card" column and weights are recorded only. They do
 * not change the school's end-of-term mix. Nothing here submits, approves,
 * or releases marks.
 *
 * Nursery tabs in the template use the same number columns. Learning-area
 * remarks are a different record and are not guessed from this file.
 */
class TermWorkbookImporter
{
    public const MAX_MARK = 100.0;

    private const ADMIN_GROUPS = [1, 3, 4];

    private const SKIP_SHEETS = ['instructions', 'setup', 'class summary'];

    private const COMPUTED = ['total', 'average', 'position'];

    public function __construct(
        private readonly ExamMarkWriter $writer,
        private readonly ExamAuthorization $authorization,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(User $actor, string $path): array
    {
        $this->assertAllowed($actor);

        $school = School::query()->find($actor->school_id);
        if ($school === null) {
            abort(403, 'Only an admin, a head teacher, or a class teacher can import a term workbook for their own school.');
        }

        $spreadsheet = IOFactory::load($path);
        $setup = $this->readSetup($spreadsheet->getSheetByName('Setup'));
        $this->assertSchoolMatches($school, $setup['school']);
        $year = $this->resolveYear($school, $setup['year']);
        $term = $this->resolveTerm($school, $year, $setup['term']);
        $sets = $this->resolveTypes($school, $setup['sets']);

        $hash = hash_file('sha256', $path) ?: '';
        $classTeacherSections = (int) $actor->usergroup_id === 5
            ? $this->authorization->sectionIdsForClassTeacher($actor, (int) $school->id, (int) $year->id)
            : null;

        $result = DB::transaction(function () use ($actor, $school, $spreadsheet, $year, $term, $sets, $hash, $classTeacherSections) {
            $rows = [];
            $counts = $this->emptyCounts();
            $classes = [];
            $setIds = [];

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $title = trim($sheet->getTitle());
                if (in_array($this->key($title), self::SKIP_SHEETS, true)) {
                    continue;
                }
                if (! str_contains($this->key($this->cell($sheet, 1, 2)), 'kls')) {
                    continue;
                }

                $sheetResult = $this->importSheet(
                    $actor,
                    $school,
                    $year,
                    $term,
                    $sheet,
                    $sets,
                    $classTeacherSections,
                    $setIds,
                );
                $setIds = $sheetResult['set_ids'];
                $classes[$title] = $sheetResult['counts'];
                foreach ($sheetResult['counts'] as $key => $value) {
                    $counts[$key] += $value;
                }
                $rows = array_merge($rows, $sheetResult['rows']);
            }

            $summary = [
                'workbook_sha256' => $hash,
                'school_id' => (int) $school->id,
                'academic_year_id' => (int) $year->id,
                'academic_term_id' => (int) $term->id,
                'exam_sets' => array_map(fn (array $set) => [
                    'name' => $set['name'],
                    'out_of' => $set['out_of'],
                    'on_the_report' => $set['on_the_report'],
                    'weight' => $set['weight'],
                    'exam_type_id' => $set['exam_type_id'],
                ], $sets),
                'counts' => $counts,
                'classes' => $classes,
                'rows' => $rows,
                'submitted' => 0,
                'composition_applied' => false,
                'notes' => [
                    'Setup report-card flags and weights were recorded and not applied. Released reports and the school mix are unchanged.',
                    'Nursery tabs are imported only when the class and subject names already exist. Learning-area remarks are not imported.',
                ],
            ];

            activity('marks_import')
                ->causedBy($actor)
                ->performedOn($term)
                ->withProperties($this->logProperties($summary))
                ->log('Term workbook imported');

            return $summary;
        });

        return $result;
    }

    private function assertAllowed(User $actor): void
    {
        if ((int) $actor->school_id <= 0) {
            abort(403, 'Only an admin, a head teacher, or a class teacher can import a term workbook for their own school.');
        }

        $group = (int) $actor->usergroup_id;
        if (in_array($group, self::ADMIN_GROUPS, true)) {
            return;
        }

        if ($group === 5) {
            $teachesClass = Section::query()
                ->where('school_id', $actor->school_id)
                ->where('class_teacher_id', $actor->id)
                ->where('status', 1)
                ->exists();

            $teachesStream = StandardLink::query()
                ->where('school_id', $actor->school_id)
                ->where('class_teacher_id', $actor->id)
                ->where(function ($query) {
                    $query->where('status', 1)->orWhere('status', '1');
                })
                ->exists();

            if ($teachesClass || $teachesStream) {
                return;
            }
        }

        abort(403, 'Only an admin, a head teacher, or a class teacher can import a term workbook for their own school.');
    }

    /**
     * @return array{school:string,year:string,term:string,sets:list<array{name:string,out_of:float,on_the_report:?string,weight:?float}>}
     */
    private function readSetup(?Worksheet $sheet): array
    {
        if ($sheet === null) {
            throw ValidationException::withMessages([
                'workbook' => 'The workbook has no Setup sheet.',
            ]);
        }

        $school = '';
        $year = '';
        $term = '';
        $headerRow = null;
        $lastRow = min((int) $sheet->getHighestRow(), 40);

        for ($row = 1; $row <= $lastRow; $row++) {
            $label = $this->key($this->cell($sheet, 1, $row));
            $value = $this->cell($sheet, 2, $row);
            if ($school === '' && $label === 'school') {
                $school = $value;
            } elseif ($year === '' && str_contains($label, 'academic year')) {
                $year = $value;
            } elseif ($term === '' && $label === 'term') {
                $term = $value;
            }

            $nameHeader = $this->key($this->cell($sheet, 2, $row));
            $outHeader = $this->key($this->cell($sheet, 3, $row));
            if (str_contains($nameHeader, 'name') && str_contains($outHeader, 'out of')) {
                $headerRow = $row;
            }
        }

        if ($school === '' || $year === '' || $term === '' || $headerRow === null) {
            throw ValidationException::withMessages([
                'workbook' => 'Setup must name the school, the academic year, the term, and the exam sets.',
            ]);
        }

        $sets = [];
        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            $name = $this->cell($sheet, 2, $row);
            if ($name === '') {
                break;
            }
            $outRaw = $this->cell($sheet, 3, $row);
            if (! is_numeric($outRaw) || (float) $outRaw <= 0) {
                throw ValidationException::withMessages([
                    'workbook' => 'Out of for '.$name.' must be a positive number.',
                ]);
            }
            $weightRaw = $this->cell($sheet, 5, $row);
            if ($weightRaw !== '' && ! is_numeric($weightRaw)) {
                throw ValidationException::withMessages([
                    'workbook' => 'The weight for '.$name.' must be a number.',
                ]);
            }
            $sets[] = [
                'name' => $name,
                'out_of' => (float) $outRaw,
                'on_the_report' => $this->cell($sheet, 4, $row) ?: null,
                'weight' => $weightRaw === '' ? null : (float) $weightRaw,
            ];
        }

        if ($sets === []) {
            throw ValidationException::withMessages([
                'workbook' => 'Setup must name at least one exam set.',
            ]);
        }

        return ['school' => $school, 'year' => $year, 'term' => $term, 'sets' => $sets];
    }

    private function assertSchoolMatches(School $school, string $workbookSchool): void
    {
        if ($this->key($school->name) !== $this->key($workbookSchool)) {
            throw ValidationException::withMessages([
                'school' => 'The workbook is for a different school, so nothing was imported.',
            ]);
        }
    }

    private function resolveYear(School $school, string $name): AcademicYear
    {
        $matches = AcademicYear::query()
            ->where('school_id', $school->id)
            ->get()
            ->filter(fn (AcademicYear $year) => $this->key((string) $year->name) === $this->key($name))
            ->values();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'academic_year' => 'The academic year in Setup is not one year at this school, so nothing was imported.',
            ]);
        }

        return $matches->first();
    }

    private function resolveTerm(School $school, AcademicYear $year, string $name): AcademicTerm
    {
        $matches = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->get()
            ->filter(fn (AcademicTerm $term) => $this->key((string) $term->name) === $this->key($name))
            ->values();

        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'academic_term' => 'The term in Setup is not one term in that year, so nothing was imported.',
            ]);
        }

        return $matches->first();
    }

    /**
     * @param  list<array{name:string,out_of:float,on_the_report:?string,weight:?float}>  $sets
     * @return list<array{name:string,out_of:float,on_the_report:?string,weight:?float,exam_type_id:?int,reason:?string}>
     */
    private function resolveTypes(School $school, array $sets): array
    {
        $types = ExamType::query()->visibleToSchool((int) $school->id)->get();

        foreach ($sets as $index => $set) {
            $matches = $types->filter(fn (ExamType $type) => $this->key((string) $type->name) === $this->key($set['name']))->values();
            $own = $matches->filter(fn (ExamType $type) => (int) $type->school_id === (int) $school->id)->values();
            $pool = $own->isNotEmpty() ? $own : $matches;

            $sets[$index]['exam_type_id'] = null;
            $sets[$index]['reason'] = null;
            if ($pool->count() === 1) {
                $sets[$index]['exam_type_id'] = (int) $pool->first()->id;
            } elseif ($pool->count() === 0) {
                $sets[$index]['reason'] = 'unknown_exam_set';
            } else {
                $sets[$index]['reason'] = 'ambiguous_exam_set';
            }
        }

        return $sets;
    }

    /**
     * @param  list<array{name:string,out_of:float,on_the_report:?string,weight:?float,exam_type_id:?int,reason:?string}>  $sets
     * @param  list<int>|null  $classTeacherSections
     * @param  array<int, int>  $setIds
     * @return array{rows:list<array<string,mixed>>,counts:array<string,int>,set_ids:array<int,int>}
     */
    private function importSheet(
        User $actor,
        School $school,
        AcademicYear $year,
        AcademicTerm $term,
        Worksheet $sheet,
        array $sets,
        ?array $classTeacherSections,
        array $setIds,
    ): array {
        $title = trim($sheet->getTitle());
        $rows = [];
        $counts = $this->emptyCounts();

        $sections = Section::query()
            ->where('school_id', $school->id)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->get()
            ->filter(fn (Section $section) => $this->key((string) $section->name) === $this->key($title))
            ->values();

        if ($sections->count() === 0) {
            $rows[] = $this->issue($title, null, null, 'unknown_class', null, null, null);
            $counts['skipped']++;

            return ['rows' => $rows, 'counts' => $counts, 'set_ids' => $setIds];
        }
        if ($sections->count() > 1) {
            $rows[] = $this->issue($title, null, null, 'ambiguous_class', null, null, null);
            $counts['skipped']++;

            return ['rows' => $rows, 'counts' => $counts, 'set_ids' => $setIds];
        }

        /** @var Section $section */
        $section = $sections->first();
        if ($classTeacherSections !== null && ! in_array((int) $section->id, $classTeacherSections, true)) {
            $rows[] = $this->issue($title, null, null, 'not_your_class', null, null, null);
            $counts['skipped']++;

            return ['rows' => $rows, 'counts' => $counts, 'set_ids' => $setIds];
        }

        $blocks = $this->blocks($sheet, $sets);
        $subjects = Subject::query()
            ->where('school_id', $school->id)
            ->where('section_id', $section->id)
            ->where('academic_year_id', $year->id)
            ->where('status', 1)
            ->get();

        $columns = [];
        foreach ($blocks as $block) {
            if ($block['exam_type_id'] === null) {
                $rows[] = $this->issue($title, null, null, $block['reason'] ?? 'unknown_exam_set', null, null, null);
                $counts['skipped']++;

                continue;
            }
            foreach ($block['columns'] as $column) {
                $matched = $subjects->filter(fn (Subject $subject) => $this->key((string) $subject->getRawOriginal('name')) === $column['key'])->values();
                if ($matched->count() !== 1) {
                    $rows[] = $this->issue($title, 2, $column['column'], $matched->count() === 0 ? 'unknown_subject' : 'ambiguous_subject', null, null, null);
                    $counts['skipped']++;

                    continue;
                }
                $subject = $matched->first();
                $exam = $this->findOrCreateExam($actor, $school, $year, $term, $section, $subject, $block, $setIds, $counts);
                if ($exam === null) {
                    $rows[] = $this->issue($title, 2, $column['column'], 'ambiguous_exam', null, null, null);
                    $counts['skipped']++;

                    continue;
                }
                $columns[] = [
                    'column' => $column['column'],
                    'header' => $column['header'],
                    'exam' => $exam,
                    'out_of' => $block['out_of'],
                ];
            }
        }

        $learners = $this->learners((int) $school->id, (int) $year->id, (int) $section->id);
        $byKls = [];
        $byName = [];
        foreach ($learners as $learner) {
            $kls = trim((string) $learner->registration_number);
            if ($kls !== '') {
                $byKls[$this->key($kls)][] = $learner;
            }
            $name = $this->personName($learner);
            if ($name !== '') {
                $byName[$this->key($name)][] = $learner;
            }
        }

        $lastRow = min((int) $sheet->getHighestRow(), 5002);
        $pending = [];
        for ($row = 3; $row <= $lastRow; $row++) {
            $kls = $this->cell($sheet, 1, $row);
            $name = $this->cell($sheet, 2, $row);
            if ($kls === '' && $name === '') {
                continue;
            }
            $resolved = $this->resolveLearner($title, $row, $kls, $name, $byKls, $byName);
            if ($resolved['student'] === null) {
                $rows[] = $resolved['issue'];
                $counts['skipped']++;

                continue;
            }
            $pending[] = ['row' => $row, 'student' => $resolved['student'], 'cells' => $this->rowCells($sheet, $row, $columns)];
        }

        $groups = [];
        foreach ($pending as $index => $item) {
            $groups[(int) $item['student']->id][] = $index;
        }
        $seenStudents = [];
        foreach ($groups as $studentId => $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $index) {
                    $rows[] = $this->issue($title, $pending[$index]['row'], null, 'duplicate_in_file', $studentId, null, null);
                    $counts['skipped']++;
                }

                continue;
            }
            $item = $pending[$indexes[0]];
            $seenStudents[$studentId] = true;
            foreach ($item['cells'] as $cell) {
                $outcome = $this->applyCell($actor, $title, $item['row'], $item['student'], $cell);
                $rows[] = $outcome['row'];
                $counts[$outcome['count']]++;
            }
        }

        foreach ($columns as $column) {
            $existing = Marks::query()
                ->where('school_id', $school->id)
                ->where('exam_id', $column['exam']->id)
                ->get();
            foreach ($existing as $mark) {
                if (isset($seenStudents[(int) $mark->student_id])) {
                    continue;
                }
                $rows[] = $this->issue($title, null, $column['column'], 'left_in_place', (int) $mark->student_id, $this->storedToken($mark), null);
                $counts['left_in_place']++;
            }
        }

        return ['rows' => $rows, 'counts' => $counts, 'set_ids' => $setIds];
    }

    /**
     * @param  list<array{name:string,out_of:float,on_the_report:?string,weight:?float,exam_type_id:?int,reason:?string}>  $sets
     * @return list<array{name:string,out_of:float,exam_type_id:?int,reason:?string,columns:list<array{column:int,header:string,key:string}>}>
     */
    private function blocks(Worksheet $sheet, array $sets): array
    {
        $lastCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $starts = [];
        for ($column = 3; $column <= $lastCol; $column++) {
            $title = $this->cell($sheet, $column, 1);
            if ($title !== '') {
                $starts[] = ['column' => $column, 'title' => $title];
            }
        }

        $blocks = [];
        foreach ($starts as $index => $start) {
            $end = $starts[$index + 1]['column'] ?? ($lastCol + 1);
            $set = $this->matchSet($start['title'], $sets);
            $columns = [];
            for ($column = $start['column']; $column < $end; $column++) {
                $header = $this->cell($sheet, $column, 2);
                if ($header === '' || in_array($this->key($header), self::COMPUTED, true)) {
                    continue;
                }
                $columns[] = ['column' => $column, 'header' => $header, 'key' => $this->key($header)];
            }
            $blocks[] = [
                'name' => $set['name'] ?? $start['title'],
                'out_of' => $set['out_of'] ?? self::MAX_MARK,
                'exam_type_id' => $set['exam_type_id'] ?? null,
                'reason' => $set['reason'] ?? 'unknown_exam_set',
                'columns' => $columns,
            ];
        }

        return $blocks;
    }

    /**
     * @param  list<array{name:string,out_of:float,exam_type_id:?int,reason:?string}>  $sets
     * @return array{name:string,out_of:float,exam_type_id:?int,reason:?string}|null
     */
    private function matchSet(string $title, array $sets): ?array
    {
        $haystack = $this->key($title);
        $matches = [];
        foreach ($sets as $set) {
            if (str_contains($haystack, $this->key($set['name']))) {
                $matches[] = $set;
            }
        }
        if (count($matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @param  array{exam_type_id:?int,out_of:float}  $block
     * @param  array<int, int>  $setIds
     * @param  array<string, int>  $counts
     */
    private function findOrCreateExam(
        User $actor,
        School $school,
        AcademicYear $year,
        AcademicTerm $term,
        Section $section,
        Subject $subject,
        array $block,
        array &$setIds,
        array &$counts,
    ): ?Exam {
        $existing = Exam::query()
            ->where('school_id', $school->id)
            ->where('academic_term_id', $term->id)
            ->where('section_id', $section->id)
            ->where('subject_id', $subject->id)
            ->where('exam_type_id', $block['exam_type_id'])
            ->whereNull('deleted_at')
            ->get();

        if ($existing->count() > 1) {
            return null;
        }
        if ($existing->count() === 1) {
            $counts['exams_skipped']++;

            return $existing->first();
        }

        $typeId = (int) $block['exam_type_id'];
        if (! isset($setIds[$typeId])) {
            $found = Exam::query()
                ->where('school_id', $school->id)
                ->where('academic_term_id', $term->id)
                ->where('exam_type_id', $typeId)
                ->whereNotNull('exam_set_id')
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->value('exam_set_id');
            if ($found) {
                $setIds[$typeId] = (int) $found;
            } else {
                $set = ExamSet::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'academic_term_id' => $term->id,
                    'created_by' => $actor->id,
                ]);
                $setIds[$typeId] = (int) $set->id;
            }
        }

        $teacherId = Teacherlink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('subject_id', $subject->id)
            ->value('teacher_id');

        $exam = Exam::create([
            'school_id' => $school->id,
            'standard_id' => $subject->standard_id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherId ? (int) $teacherId : (int) $actor->id,
            'exam_type_id' => $typeId,
            'exam_set_id' => $setIds[$typeId],
            'status' => 'undone',
        ]);
        $counts['exams_created']++;

        return $exam;
    }

    /**
     * @param  array<string, list<User>>  $byKls
     * @param  array<string, list<User>>  $byName
     * @return array{student:?User,issue:?array<string,mixed>}
     */
    private function resolveLearner(string $sheet, int $row, string $kls, string $name, array $byKls, array $byName): array
    {
        if ($kls !== '') {
            $found = $byKls[$this->key($kls)] ?? [];
            if (count($found) === 1) {
                return ['student' => $found[0], 'issue' => null];
            }

            return [
                'student' => null,
                'issue' => $this->issue($sheet, $row, 1, count($found) > 1 ? 'ambiguous' : 'unknown_student', null, null, null),
            ];
        }

        $found = $byName[$this->key($name)] ?? [];
        if (count($found) === 1) {
            return ['student' => $found[0], 'issue' => null];
        }

        return [
            'student' => null,
            'issue' => $this->issue($sheet, $row, 2, count($found) > 1 ? 'ambiguous' : 'no_match', null, null, null),
        ];
    }

    /**
     * @param  list<array{column:int,header:string,exam:Exam,out_of:float}>  $columns
     * @return list<array{column:int,header:string,exam:Exam,out_of:float,raw:string}>
     */
    private function rowCells(Worksheet $sheet, int $row, array $columns): array
    {
        $cells = [];
        foreach ($columns as $column) {
            $cells[] = $column + ['raw' => $this->cell($sheet, $column['column'], $row)];
        }

        return $cells;
    }

    /**
     * @param  array{column:int,header:string,exam:Exam,out_of:float,raw:string}  $cell
     * @return array{row:array<string,mixed>,count:string}
     */
    private function applyCell(User $actor, string $sheet, int $row, User $student, array $cell): array
    {
        $raw = trim($cell['raw']);
        $exam = $cell['exam'];
        $saved = Marks::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->get();

        if ($saved->count() > 1) {
            return [
                'count' => 'skipped',
                'row' => $this->issue($sheet, $row, $cell['column'], 'duplicate_existing', (int) $student->id, null, null),
            ];
        }

        $existing = $saved->first();
        $old = $existing ? $this->storedToken($existing) : null;

        if ($raw === '') {
            if ($existing) {
                return [
                    'count' => 'left_in_place',
                    'row' => $this->issue($sheet, $row, $cell['column'], 'left_in_place', (int) $student->id, $old, null),
                ];
            }

            return [
                'count' => 'blank',
                'row' => $this->issue($sheet, $row, $cell['column'], 'blank', (int) $student->id, null, null),
            ];
        }

        $parsed = $this->parseMark($raw, (float) $cell['out_of']);
        if ($parsed['reason'] !== null) {
            return [
                'count' => 'skipped',
                'row' => $this->issue($sheet, $row, $cell['column'], $parsed['reason'], (int) $student->id, $old, null),
            ];
        }

        $new = $parsed['token'];
        if ($old !== null && $old === $new) {
            return [
                'count' => 'unchanged',
                'row' => $this->issue($sheet, $row, $cell['column'], 'unchanged', (int) $student->id, $old, $new),
            ];
        }

        try {
            $this->writer->save($actor, $exam, (int) $student->id, $parsed['payload']);
        } catch (MarksLockedException) {
            return [
                'count' => 'skipped',
                'row' => $this->issue($sheet, $row, $cell['column'], 'locked', (int) $student->id, $old, $new),
            ];
        } catch (HttpException) {
            return [
                'count' => 'skipped',
                'row' => $this->issue($sheet, $row, $cell['column'], 'not_allowed', (int) $student->id, $old, $new),
            ];
        }

        return [
            'count' => $old === null ? 'written' : 'updated',
            'row' => $this->issue($sheet, $row, $cell['column'], $old === null ? 'written' : 'updated', (int) $student->id, $old, $new),
        ];
    }

    /**
     * @return array{reason:?string,token:?string,payload:mixed}
     */
    private function parseMark(string $raw, float $outOf): array
    {
        if (in_array($this->key($raw), ['ab', 'abs'], true)) {
            return ['reason' => null, 'token' => 'ABS', 'payload' => 'AB'];
        }

        $text = preg_match('/^\d+,\d{1,2}$/', $raw) ? str_replace(',', '.', $raw) : $raw;
        if (! is_numeric($text)) {
            return ['reason' => 'not_a_number', 'token' => null, 'payload' => null];
        }

        $mark = round((float) $text, 2);
        $limit = min(self::MAX_MARK, $outOf);
        if ($mark < 0 || $mark > $limit) {
            return ['reason' => 'out_of_range', 'token' => null, 'payload' => null];
        }

        $token = floor($mark) == $mark ? (string) (int) $mark : rtrim(rtrim(number_format($mark, 2, '.', ''), '0'), '.');

        return ['reason' => null, 'token' => $token, 'payload' => $mark];
    }

    private function storedToken(Marks $mark): string
    {
        if ($mark->isAbsent() || $mark->marks === null) {
            return 'ABS';
        }

        $value = round((float) $mark->marks, 2);

        return floor($value) == $value ? (string) (int) $value : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @return list<User>
     */
    private function learners(int $schoolId, int $yearId, int $sectionId): array
    {
        $linkIds = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('section_id', $sectionId)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->pluck('id');

        return User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereIn('id', StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $yearId)
                ->whereIn('standardLink_id', $linkIds)
                ->pluck('user_id'))
            ->with('userprofile')
            ->get()
            ->all();
    }

    private function personName(User $user): string
    {
        $profile = $user->userprofile;
        $fromProfile = trim(((string) ($profile->firstname ?? '')).' '.((string) ($profile->lastname ?? '')));
        $fromProfile = preg_replace('/\s+/u', ' ', $fromProfile) ?? $fromProfile;
        if ($fromProfile !== '') {
            return $fromProfile;
        }

        return trim((string) $user->name);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function logProperties(array $summary): array
    {
        $rows = [];
        foreach ($summary['rows'] as $row) {
            if (in_array($row['reason'], ['blank', 'unchanged'], true)) {
                continue;
            }
            $rows[] = [
                'sheet' => $row['sheet'],
                'row' => $row['row'],
                'column' => $row['column'],
                'reason' => $row['reason'],
                'student_id' => $row['student_id'],
                'old' => $row['old'],
                'new' => $row['new'],
            ];
        }

        return [
            'action' => 'term_workbook_imported',
            'workbook_sha256' => $summary['workbook_sha256'],
            'academic_year_id' => $summary['academic_year_id'],
            'academic_term_id' => $summary['academic_term_id'],
            'exam_sets' => $summary['exam_sets'],
            'counts' => $summary['counts'],
            'classes' => $summary['classes'],
            'rows' => $rows,
            'submitted' => 0,
            'composition_applied' => false,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts(): array
    {
        return [
            'written' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'blank' => 0,
            'left_in_place' => 0,
            'skipped' => 0,
            'exams_created' => 0,
            'exams_skipped' => 0,
        ];
    }

    /**
     * @return array{sheet:string,row:?int,column:?int,reason:string,student_id:?int,old:?string,new:?string}
     */
    private function issue(string $sheet, ?int $row, ?int $column, string $reason, ?int $studentId, ?string $old, ?string $new): array
    {
        return [
            'sheet' => $sheet,
            'row' => $row,
            'column' => $column,
            'reason' => $reason,
            'student_id' => $studentId,
            'old' => $old,
            'new' => $new,
        ];
    }

    private function cell(Worksheet $sheet, int $column, int $row): string
    {
        $value = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getCalculatedValue();
        if ($value === null) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            if (floor((float) $value) == (float) $value) {
                return (string) (int) $value;
            }

            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    private function key(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }
}
