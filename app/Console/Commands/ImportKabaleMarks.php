<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Subject;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ImportKabaleMarks extends Command
{
    protected $signature = 'kabale:import-marks
                            {--dry-run : Report matches without writing to DB}
                            {--map= : Optional JSON file mapping Excel column headers to subject names}
                            {--class= : Class to import (e.g. p1East, p1West, p2, p3East, p3West, p4East, p4West, p5East, p5West, p6East, p6West, p7A, p7B)}';

    protected $description = 'Import marks for Kabale (school 104) from Excel files. Reads all subject columns dynamically.';

    private const CLASS_CONFIG = [
        'p1East'  => ['section' => 'P.1',  'standard' => 'primary_lower'],
        'p1West'  => ['section' => 'P.1',  'standard' => 'primary_lower'],
        'p2'      => ['section' => 'P.2',  'standard' => 'primary_lower'],
        'p3East'  => ['section' => 'P.3',  'standard' => 'primary_lower'],
        'p3West'  => ['section' => 'P.3',  'standard' => 'primary_lower'],
        'p4East'  => ['section' => 'P.4',  'standard' => 'primary'],
        'p4West'  => ['section' => 'P.4',  'standard' => 'primary'],
        'p5East'  => ['section' => 'P.5',  'standard' => 'primary'],
        'p5West'  => ['section' => 'P.5',  'standard' => 'primary'],
        'p6East'  => ['section' => 'P.6',  'standard' => 'primary'],
        'p6West'  => ['section' => 'P.6',  'standard' => 'primary'],
        'p7A'     => ['section' => 'P.7',  'standard' => 'primary'],
        'p7B'     => ['section' => 'P.7',  'standard' => 'primary'],
    ];


    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $classKey = $this->option('class') ?? 'p2';
        $school = School::findOrFail(104);

        $config = self::CLASS_CONFIG[$classKey] ?? null;
        if (!$config) {
            $this->error("Unknown class: {$classKey}. Valid: " . implode(', ', array_keys(self::CLASS_CONFIG)));
            return 1;
        }

        $this->info("School: {$school->name}");
        $this->info("Class: {$config['section']} ({$config['standard']})");

        $stdLink = StandardLink::where('school_id', 104)
            ->whereHas('section', fn($q) => $q->where('name', $config['section']))
            ->whereHas('standard', fn($q) => $q->where('name', $config['standard']))
            ->first();

        if (!$stdLink) {
            $this->error("standards_link not found for {$config['section']} / {$config['standard']}.");
            return 1;
        }

        $this->info("Class: {$config['section']} ({$config['standard']}) stdlink_id={$stdLink->id} section_id={$stdLink->section_id}");

        $term = AcademicTerm::where('school_id', 104)
            ->where('name', 'like', '%Term II%')
            ->orWhere('name', 'like', '%Term 2%')
            ->first();

        $year = AcademicYear::where('school_id', 104)->where('status', 1)->first();

        $this->info("Term: " . ($term?->name ?? 'NOT FOUND') . " Year: " . ($year?->name ?? 'NOT FOUND'));

        if (!$term || !$year) {
            $this->error('Term or year not found.');
            return 1;
        }

        $examTypeMid = ExamType::where('code', 'MID')->first();
        $examTypeEot = ExamType::where('code', 'EOT')->first();

        $roster = $this->buildRoster($stdLink->id);
        $this->info("Roster (filtered): " . count($roster) . " students");

        $fileLabel = str_replace(' ', '', $config['section']);
        $files = [
            'June' => "/tmp/{$fileLabel}_june.xlsx",
            'July' => "/tmp/{$fileLabel}_july.xlsx",
            'EOT'  => "/tmp/{$fileLabel}_end_of_term.xlsx",
        ];

        // Build subject map: either from --map JSON file or auto-detected from Excel headers
        $subjectMap = $this->buildSubjectMap($files, $dryRun);
        if (empty($subjectMap)) {
            $this->error('No subject columns found in Excel files.');
            return 1;
        }
        $this->info('Subject columns detected: ' . implode(', ', array_values($subjectMap)));

        $subjects = $this->ensureSubjects($school->id, $stdLink, $subjectMap);
        $conflicts = [];
        $unmatched = ['June' => [], 'July' => [], 'EOT' => []];
        $matched = [];

        DB::beginTransaction();
        try {
            foreach ($files as $label => $path) {
                if (!file_exists($path)) {
                    $this->error("File not found: {$path}");
                    continue;
                }
                $sheets = Excel::toArray(new SheetsOnlyImport, $path);
                $rawRows = $sheets[0] ?? [];
                $headers = array_map(fn($h) => strtolower(str_replace([' ', '.'], '_', (string) $h)), $rawRows[0] ?? []);
                $dataRows = array_slice($rawRows, 1);
                $examType = ($label === 'EOT') ? $examTypeEot : $examTypeMid;
                $month = ($label === 'June') ? 6 : (($label === 'July') ? 7 : 8);

                $examDate = Carbon::create(2026, $month, 1);
                $exam = $this->findOrCreateExam($school->id, $stdLink, $examType, $examDate, $term, $year, $dryRun);

                $this->line("\n--- {$label} ({$examType->code}) exam_id={$exam->id} ---");

                foreach ($dataRows as $rawRow) {
                    $row = array_combine($headers, array_values($rawRow));
                    $excelName = trim((string) ($row['name'] ?? ''));
                    if ($excelName === '') continue;

                    $match = $this->matchStudent($excelName, $roster);

                    if ($match === null) {
                        $unmatched[$label][strtolower($excelName)] = $excelName;
                        // Debug first few: show best score
                        if (count($unmatched[$label]) <= 3) {
                            $this->debugBestScore($excelName, $roster);
                        }
                        $this->warn("  UNMATCHED: {$excelName}");
                        continue;
                    }

                    if (is_string($match)) {
                        $conflicts[] = ['file' => $label, 'name' => $excelName, 'reason' => $match];
                        $this->warn("  CONFLICT: {$excelName} — {$match}");
                        continue;
                    }

                    $studentId = $match['id'];
                    $studentName = $match['name'];

                    foreach ($subjectMap as $colKey => $subjectName) {
                        $score = $row[$colKey] ?? null;
                        if ($score === null || (string) $score === '') continue;
                        $score = (int) $score;

                        $subjectId = $subjects[$subjectName] ?? null;
                        if (!$subjectId) continue;

                        if (!$dryRun) {
                            $grade = $this->resolveGrade($school->id, $stdLink->standard_id, $score);
                            Marks::updateOrCreate(
                                [
                                    'student_id' => $studentId,
                                    'exam_id'    => $exam->id,
                                    'school_id'  => $school->id,
                                    'subject_id' => $subjectId,
                                ],
                                [
                                    'teacher_id' => $exam->teacher_id ?? 0,
                                    'marks'      => $score,
                                    'section_id' => $stdLink->section_id,
                                    'grade'      => $grade,
                                ]
                            );
                        }
                    }

                    $key = "{$studentId}|{$label}";
                    if (!isset($matched[$key])) {
                        $matched[$key] = ['name' => $studentName, 'student_id' => $studentId, 'label' => $label];
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info("\nDry run — changes rolled back.");
            } else {
                DB::commit();
                $this->info("\nImport committed.");
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        // Backfill marks.grade for all newly imported marks using the grading system
        if (!$dryRun) {
            $this->backfillMarksGrade($school->id, $stdLink->standard_id);
        }

        $this->report($matched, $unmatched, $conflicts, $files);
        return 0;
    }

    private function findP2StandardLink(): ?StandardLink
    {
        return StandardLink::where('school_id', 104)
            ->where('standard_id', 54)
            ->whereHas('section', fn($q) => $q->where('name', 'P.2'))
            ->first();
    }

    private function buildRoster(int $stdLinkId): array
    {
        $raw = DB::table('student_academics')
            ->join('users', 'student_academics.user_id', '=', 'users.id')
            ->where('student_academics.standardLink_id', $stdLinkId)
            ->select('users.id', 'users.name')
            ->get();

        $this->line("  DEBUG buildRoster raw count: " . $raw->count());

        $roster = [];
        $filteredOut = 0;
        foreach ($raw as $s) {
            if (preg_match('/[0-9]/', $s->name)) { $filteredOut++; continue; } // skip auto-generated names
            $key = preg_replace('/[^a-z]/', '', strtolower($s->name));
            if (!isset($roster[$key]) || strlen($s->name) > strlen($roster[$key]['name'])) {
                $roster[$key] = ['id' => $s->id, 'name' => $s->name];
            }
        }
        $this->line("  DEBUG filtered out (digits): {$filteredOut}, kept: " . count($roster));
        return array_values($roster);
    }

    private function matchStudent(string $excelName, array $roster): null|string|array
    {
        if ($excelName === 'Atukunda Shivan Esther' || $excelName === 'Atukunda Shivan E') {
            return 'CONFLICT_DUPLICATE: Two rows for same student in July sheet — manual review needed';
        }

        $bestScore = 0;
        $bestMatch = null;
        $excelTokens = $this->tokenize($excelName);

        foreach ($roster as $student) {
            $dbTokens = $this->tokenize($student['name']);
            $score = $this->matchScore($excelTokens, $dbTokens);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $student;
            }
        }

        if ($bestScore >= 50) {
            return $bestMatch;
        }

        return null;
    }

    private function tokenize(string $name): array
    {
        return array_map('strtolower', preg_split('/\s+/', trim($name)));
    }

    private function matchScore(array $tokensA, array $tokensB): float
    {
        $common = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));
        if (empty($union)) return 0;

        $jaccard = count($common) / count($union) * 100;

        sort($tokensA); sort($tokensB);
        similar_text(implode('', $tokensA), implode('', $tokensB), $simPct);

        return ($jaccard * 0.4) + ($simPct * 0.6);
    }

    private function debugBestScore(string $excelName, array $roster): void
    {
        $bestScore = 0;
        $bestMatch = null;
        $excelTokens = $this->tokenize($excelName);
        foreach ($roster as $student) {
            $dbTokens = $this->tokenize($student['name']);
            $score = $this->matchScore($excelTokens, $dbTokens);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $student;
            }
        }
        $this->warn("    best: score={$bestScore} match=" . ($bestMatch['name'] ?? 'none') . " tokens=" . implode('|', $excelTokens));
    }

    private function ensureSubjects(int $schoolId, StandardLink $stdLink, array $subjectMap): array
    {
        $existingSubjects = Subject::where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->get()
            ->keyBy(function ($s) { return strtoupper(str_replace(' ', '', $s->name)); });

        $map = [];
        foreach ($subjectMap as $colKey => $subjectName) {
            $canonical = $this->findCanonicalSubject($subjectName, $existingSubjects);

            if ($canonical) {
                $s = $canonical;
                $this->line("  Matched '{$subjectName}' → existing '{$s->name}' (id={$s->id})");
            } else {
                $s = Subject::updateOrCreate(
                    ['school_id' => $schoolId, 'section_id' => $stdLink->section_id, 'name' => strtoupper($subjectName)],
                    ['code' => '', 'type' => 'core', 'status' => 1, 'academic_year_id' => $stdLink->academic_year_id, 'standard_id' => $stdLink->standard_id]
                );
                $this->line("  Created new subject '{$s->name}' (id={$s->id})");
            }
            $map[$colKey] = $s->id;
        }
        return $map;
    }

    private function findCanonicalSubject(string $detectedName, \Illuminate\Support\Collection $existing): ?Subject
    {
        $normalized = strtoupper(str_replace(' ', '', $detectedName));

        if ($existing->has($normalized)) {
            return $existing->get($normalized);
        }

        $aliases = $this->subjectAliases();
        if (isset($aliases[$normalized]) && $existing->has($aliases[$normalized])) {
            return $existing->get($aliases[$normalized]);
        }

        return null;
    }

    private function subjectAliases(): array
    {
        return [
            'ENG' => 'ENGLISH',
            'ENGLISH' => 'ENGLISH',
            'MTC' => 'MTC',
            'MATHEMATICS' => 'MTC',
            'MATH' => 'MTC',
            'RE' => 'RELIGIOUSEDUCATION',
            'RELIGIOUSEDUCATION' => 'RELIGIOUSEDUCATION',
            'LITI' => 'LITERACYI',
            'LITI' => 'LITERACYI',
            'LITERACYI' => 'LITERACYI',
            'LITII' => 'LITERACYII',
            'LITII' => 'LITERACYII',
            'LITERACYII' => 'LITERACYII',
            'RR' => 'READINGANDRESPONSE',
            'READINGANDRESPONSE' => 'READINGANDRESPONSE',
            'R_R' => 'READINGANDRESPONSE',
        ];
    }

    private function buildSubjectMap(array $files, bool $dryRun): array
    {
        $mapFile = $this->option('map');
        if ($mapFile && file_exists($mapFile)) {
            $map = json_decode(file_get_contents($mapFile), true);
            if (is_array($map)) {
                $this->info("Using subject map from: {$mapFile}");
                return $map;
            }
            $this->warn("Invalid JSON in map file, falling back to auto-detection.");
        }

        $nonSubjects = ['name', 'no', 'no_', '#', 'total', 'position', 'pos', 'rank', 'average', 'avg', 'remarks', 'comment'];

        foreach ($files as $path) {
            if (!file_exists($path)) continue;
            $sheets = Excel::toArray(new SheetsOnlyImport, $path);
            $headers = $sheets[0][0] ?? [];
            $map = [];
            foreach ($headers as $h) {
                $raw = trim((string) $h);
                if ($raw === '') continue;
                $key = strtolower(str_replace([' ', '.'], '_', $raw));
                if (in_array($key, $nonSubjects)) continue;
                $map[$key] = $this->normalizeSubjectName($raw);
            }
            if (!empty($map)) {
                $this->info("Auto-detected subject columns: " . implode(', ', array_values($map)));
                return $map;
            }
        }

        return [];
    }

    private function normalizeSubjectName(string $raw): string
    {
        $name = trim(str_replace(['_', '.'], ' ', $raw));
        $name = preg_replace('/\s+/', ' ', $name);
        $name = ucwords(strtolower($name));
        if ($name === '') $name = trim($raw);
        return $name;
    }

    private function resolveGrade(int $schoolId, int $standardId, int $score): string
    {
        $grade = DB::table('school_grading_systems')
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->value('grade');

        return $grade ?? '';
    }

    private function backfillMarksGrade(int $schoolId, int $standardId): void
    {
        $grades = DB::table('school_grading_systems')
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->get();

        if ($grades->isEmpty()) return;

        $updated = 0;
        foreach ($grades as $g) {
            $updated += DB::table('marks')
                ->where('school_id', $schoolId)
                ->whereNull('grade')
                ->where('marks', '>=', $g->min_score)
                ->where('marks', '<=', $g->max_score)
                ->update(['grade' => $g->grade]);
        }

        $this->info("  Backfilled marks.grade: {$updated} rows updated.");
    }

    private function findOrCreateExam(int $schoolId, StandardLink $stdLink, ExamType $examType, Carbon $date, AcademicTerm $term, AcademicYear $year, bool $dryRun): Exam
    {
        $existing = Exam::where('school_id', $schoolId)
            ->where('standard_id', $stdLink->standard_id)
            ->where('section_id', $stdLink->section_id)
            ->where('exam_type_id', $examType->id)
            ->where('academic_term_id', $term->id)
            ->where('academic_year_id', $year->id)
            ->whereMonth('scheduled_at', $date->month)
            ->first();

        if ($existing) {
            return $existing;
        }

        $firstTeacher = DB::table('users')
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->value('id') ?? 0;

        $firstSubject = DB::table('subjects')
            ->where('school_id', $schoolId)
            ->value('id') ?? 0;

        $exam = new Exam([
            'school_id'        => $schoolId,
            'standard_id'      => $stdLink->standard_id,
            'section_id'       => $stdLink->section_id,
            'subject_id'       => $firstSubject,
            'teacher_id'       => $firstTeacher,
            'exam_type_id'     => $examType->id,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at'     => $date,
            'status'           => 'undone',
        ]);

        if (!$dryRun) {
            $exam->save();
        }

        $this->line("  Created exam: {$examType->code} ({$date->format('Y-m-d')}) std={$stdLink->standard_id} sec={$stdLink->section_id}");
        return $exam;
    }

    private function report(array $matched, array $unmatched, array $conflicts, array $files): void
    {
        $this->line("\n========================================");
        $this->info("IMPORT SUMMARY");
        $this->line("========================================");

        $byLabel = [];
        foreach ($matched as $m) {
            $byLabel[$m['label']][] = $m['name'];
        }
        foreach ($files as $label => $path) {
            $count = count($byLabel[$label] ?? []);
            $this->line("  {$label}: {$count} students matched");
        }
        $this->line("  Total student-exam combinations: " . count($matched));

        if (array_filter($unmatched)) {
            $totalUnmatched = 0;
            foreach ($unmatched as $label => $names) {
                if ($names) {
                    $totalUnmatched += count($names);
                    $this->warn("\n  {$label} UNMATCHED: " . count($names));
                    foreach ($names as $name) {
                        $this->warn("    - {$name}");
                    }
                }
            }

            $eotOnly = array_diff_key($unmatched['EOT'], $unmatched['June'], $unmatched['July']);
            if ($eotOnly) {
                $this->warn("\nEOT-ONLY names (possible new admissions — confirm these are real enrolled P.2 students):");
                foreach ($eotOnly as $name) {
                    $this->warn("  - {$name}");
                }
            }
        } else {
            $this->info("\nUNMATCHED: 0 — all rows matched");
        }

        if ($conflicts) {
            $this->warn("\nCONFLICTS (held for manual review):");
            foreach ($conflicts as $c) {
                $this->warn("  {$c['name']} — {$c['reason']}");
            }
        }
    }
}

class SheetsOnlyImport implements ToCollection
{
    public function collection(Collection $collection) {}
}