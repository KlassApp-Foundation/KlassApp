<?php

namespace App\Services\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\OnboardingPlan;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\StudentIdGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Turns a checklist workbook into a school-scoped plan, previews each step
 * against the rows already stored, and applies one step through OnboardingEngine.
 *
 * Card numbers are copied off the plan. Nothing in a card is asked of a model.
 */
final class ChecklistOnboardingService
{
    public const SCHEMA = 1;

    /** @var list<string> */
    public const WALK = ['terms', 'standards', 'subjects', 'teachers', 'students'];

    /** @var array<string, string> */
    private const TITLES = [
        'terms' => 'Academic terms',
        'standards' => 'Classes and streams',
        'subjects' => 'Subjects',
        'teachers' => 'Teachers and teaching assignments',
        'students' => 'Learners and parents',
    ];

    public function __construct(
        private ChecklistWorkbookConverter $converter,
        private ChecklistOnboardingGate $gate,
        private OnboardingEngine $engine,
    ) {}

    /**
     * @return array{ok: bool, code: string, plan_id?: int, card: array<string, mixed>}
     */
    public function ingest(School $school, User $actor, string $path, string $originalName): array
    {
        $this->assertActor($school, $actor);
        if (! is_file($path)) {
            return ['ok' => false, 'code' => 'unreadable', 'card' => $this->failedCard('The checklist file could not be read.')];
        }

        try {
            $converted = $this->converter->convert($path);
        } catch (\Throwable $e) {
            Log::warning('Checklist workbook could not be read', [
                'school_id' => $school->id,
                'exception' => $e::class,
            ]);

            return ['ok' => false, 'code' => 'unreadable', 'card' => $this->failedCard('This file could not be read as a checklist workbook.')];
        }

        if (($converted['blocked'] ?? null) !== null) {
            return ['ok' => false, 'code' => 'dpa', 'card' => $this->refusalCard($converted)];
        }

        $payload = $this->payload($converted, $path, $originalName);
        $plan = OnboardingPlan::query()->create([
            'school_id' => $school->id,
            'created_by' => $actor->id,
            'source_sha256' => $payload['source']['sha256'],
            'source_name' => $originalName,
            'schema' => self::SCHEMA,
            'status' => 'previewed',
            'dpa_confirmed_at' => now(),
            'expires_at' => now()->addDays(30),
            'payload' => $payload,
        ]);

        return ['ok' => true, 'code' => 'summary', 'plan_id' => $plan->id, 'card' => $this->summaryCard($payload)];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function skipFlags(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireDpa($plan);
        $payload['flags_skipped'] = true;
        $payload['skipped_flags'] = $this->errorFlags($payload);
        foreach (self::WALK as $key) {
            $payload['steps'][$key]['rows'] = array_values(array_filter(
                $payload['steps'][$key]['rows'],
                fn (array $row): bool => ! $this->rowIsFlagged($payload['skipped_flags'], $row['source']['tab'], (int) $row['source']['row'])
            ));
        }
        $this->savePayload($plan, $payload, 'previewed');

        return ['ok' => true, 'code' => 'preview', 'card' => $this->previewCard($plan->fresh(), $actor)];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function abandon(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $plan->status = 'abandoned';
        $plan->save();

        return [
            'ok' => true,
            'code' => 'stopped',
            'card' => [
                'kind' => 'stopped',
                'message' => 'Stopped. Fix the workbook, then upload it again. Nothing was written.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function previewCard(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireReady($plan);
        $key = $this->currentKey($payload);
        if ($key === null) {
            return $this->completionCard($plan->school, $payload);
        }

        $preview = $this->previewStep($plan->school, $payload, $key);

        return [
            'kind' => 'preview',
            'step_key' => $key,
            'title' => self::TITLES[$key],
            'create' => $preview['create'],
            'exists' => $preview['exists'],
            'conflict' => $preview['conflict'],
            'examples' => array_slice($preview['examples'], 0, 3),
            'conflicts' => array_slice($preview['conflicts'], 0, 3),
        ];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function apply(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireReady($plan);
        $key = $this->currentKey($payload);
        if ($key === null) {
            return ['ok' => true, 'code' => 'done', 'card' => $this->completionCard($plan->school, $payload)];
        }

        $preview = $this->previewStep($plan->school, $payload, $key);
        if ($preview['conflict'] > 0) {
            return [
                'ok' => false,
                'code' => 'conflict',
                'card' => [
                    'kind' => 'failed',
                    'title' => self::TITLES[$key],
                    'message' => 'This step was not applied. A conflicting row has to be fixed in the workbook, or skip the step. Nothing from this step was saved.',
                    'conflicts' => array_slice($preview['conflicts'], 0, 3),
                ],
            ];
        }

        $beforeInvites = TeacherInvite::query()->where('school_id', $plan->school_id)->count();
        try {
            DB::transaction(function () use ($plan, $payload, $key, $preview): void {
                ChecklistInviteHold::hold(function () use ($plan, $payload, $key, $preview): void {
                    if ($preview['create'] > 0) {
                        $this->writeStep($plan->school, $payload, $key, $preview['creates']);
                    }
                });
            });
        } catch (ValidationException $e) {
            Log::warning('Checklist onboarding step refused', [
                'school_id' => $plan->school_id,
                'step' => $key,
                'exception' => $e::class,
            ]);

            return [
                'ok' => false,
                'code' => 'failed',
                'card' => [
                    'kind' => 'failed',
                    'title' => self::TITLES[$key],
                    'message' => collect($e->errors())->flatten()->first()
                        ?: 'This step was not applied. Nothing from this step was saved.',
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('Checklist onboarding step failed', [
                'school_id' => $plan->school_id,
                'step' => $key,
                'exception' => $e::class,
            ]);

            return [
                'ok' => false,
                'code' => 'failed',
                'card' => [
                    'kind' => 'failed',
                    'title' => self::TITLES[$key],
                    'message' => 'This step was not applied. Nothing from this step was saved.',
                ],
            ];
        }

        $payload['invites_ready'] = (int) ($payload['invites_ready'] ?? 0)
            + max(0, TeacherInvite::query()->where('school_id', $plan->school_id)->count() - $beforeInvites);
        $payload['results'][$key] = [
            'created' => $preview['create'],
            'existed' => $preview['exists'],
        ];
        $payload['walk_index'] = (int) $payload['walk_index'] + 1;
        $status = $this->currentKey($payload) === null ? 'applied' : 'applying';
        $this->savePayload($plan, $payload, $status);

        return ['ok' => true, 'code' => 'result', 'card' => $this->resultCard($plan->school, $payload, $key, $preview)];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function skipStep(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireReady($plan);
        $key = $this->currentKey($payload);
        if ($key === null) {
            return ['ok' => true, 'code' => 'done', 'card' => $this->completionCard($plan->school, $payload)];
        }

        $payload['skipped_steps'][] = $key;
        $payload['walk_index'] = (int) $payload['walk_index'] + 1;
        $status = $this->currentKey($payload) === null ? 'applied' : 'applying';
        $this->savePayload($plan, $payload, $status);
        $next = $this->currentKey($payload);
        if ($next === null) {
            return ['ok' => true, 'code' => 'done', 'card' => $this->completionCard($plan->school, $payload)];
        }

        return ['ok' => true, 'code' => 'preview', 'card' => $this->previewCard($plan->fresh(), $actor)];
    }

    /**
     * @param  array<string, mixed>  $converted
     * @return array<string, mixed>
     */
    private function payload(array $converted, string $path, string $originalName): array
    {
        foreach (self::WALK as $key) {
            if (! array_key_exists($key, OnboardingStepsService::ALL_STEPS)) {
                throw new \RuntimeException('Checklist step is not in OnboardingStepsService.');
            }
        }

        $planRows = $converted['planRows'] ?? [];
        $flags = array_values(array_filter(
            $converted['flags'] ?? [],
            fn (array $flag): bool => ($flag['severity'] ?? 'error') === 'error'
        ));
        $notes = array_values(array_filter(
            $converted['flags'] ?? [],
            fn (array $flag): bool => ($flag['severity'] ?? 'error') === 'info'
        ));

        $classNames = [];
        $classRows = [];
        $classTeachers = [];
        foreach ($planRows['classes'] ?? [] as $class) {
            $classNames[$class['class']] = $class['class'];
            $classRows[] = [
                'source' => ['tab' => 'Classes & Streams', 'row' => (int) $class['row']],
                'name' => $class['class'],
                'stream' => (string) ($class['stream'] ?? ''),
            ];
            if (trim((string) ($class['teacher'] ?? '')) !== '') {
                $classTeachers[] = [
                    'class' => $class['class'],
                    'stream' => (string) ($class['stream'] ?? ''),
                    'name' => $class['teacher'],
                ];
            }
        }

        $subjectRows = [];
        foreach ($planRows['subjects'] ?? [] as $subject) {
            $subjectRows[] = [
                'source' => ['tab' => 'Subjects', 'row' => (int) $subject['row']],
                'name' => $subject['name'],
                'classes' => $this->classesForSubject((string) ($subject['classes'] ?? ''), array_values($classNames)),
            ];
        }

        $linksByTeacher = [];
        foreach ($planRows['assignments'] ?? [] as $link) {
            $linksByTeacher[$this->key((string) $link['teacher'])][] = [
                'source' => ['tab' => 'Teaching Assignments', 'row' => (int) $link['row']],
                'subject' => $link['subject'],
                'class' => $link['class'],
                'stream' => (string) ($link['stream'] ?? ''),
            ];
        }

        $teacherRows = [];
        foreach ($planRows['teachers'] ?? [] as $teacher) {
            $teacherRows[] = [
                'source' => ['tab' => 'Teachers', 'row' => (int) $teacher['row']],
                'name' => $teacher['name'],
                'email' => (string) ($teacher['email'] ?? ''),
                'phone' => (string) ($teacher['phone'] ?? ''),
                'links' => $linksByTeacher[$this->key((string) $teacher['name'])] ?? [],
            ];
        }

        $studentRows = [];
        foreach ($planRows['students'] ?? [] as $student) {
            $studentRows[] = [
                'source' => ['tab' => 'Learners', 'row' => (int) $student['row']],
                'name' => $student['name'],
                'class' => $student['class'],
                'stream' => (string) ($student['stream'] ?? ''),
                'gender' => strtolower((string) ($student['gender'] ?? '')),
                'date_of_birth' => (string) ($student['date_of_birth'] ?? ''),
                'parent_name' => (string) ($student['parent_name'] ?? ''),
                'parent_phone' => (string) ($student['parent_phone'] ?? ''),
                'parent_email' => (string) ($student['parent_email'] ?? ''),
                'school_student_id' => (string) ($student['school_student_id'] ?? ''),
            ];
        }

        $termRows = [];
        foreach ($planRows['terms'] ?? [] as $term) {
            $termRows[] = [
                'source' => ['tab' => 'Terms & Exams', 'row' => (int) $term['row']],
                'year' => (string) $term['year'],
                'name' => (string) $term['name'],
                'start' => (string) $term['start'],
                'end' => (string) $term['end'],
            ];
        }

        $steps = [
            'terms' => $this->step('terms', $termRows, $converted, 'Terms & Exams'),
            'standards' => $this->step('standards', $classRows, $converted, 'Classes & Streams'),
            'subjects' => $this->step('subjects', $subjectRows, $converted, 'Subjects'),
            'teachers' => $this->step('teachers', $teacherRows, $converted, 'Teachers'),
            'students' => $this->step('students', $studentRows, $converted, 'Learners'),
        ];
        $steps['teachers']['counts']['flagged'] += $this->flagCount($converted['flags'] ?? [], 'Teaching Assignments');

        return [
            'schema' => self::SCHEMA,
            'source' => [
                'name' => $originalName,
                'sha256' => hash_file('sha256', $path) ?: '',
            ],
            'school' => [
                'name' => (string) ($converted['school'] ?? ''),
                'country' => '',
            ],
            'dpa' => [
                'confirmed' => true,
                'tab' => (string) ($converted['dpa']['tab'] ?? 'Confirmations'),
                'row' => $converted['dpa']['row'] ?? null,
                'answer' => (string) ($converted['dpa']['answer'] ?? 'Yes'),
            ],
            'steps' => $steps,
            'flags' => array_map(fn (array $flag): array => [
                'tab' => (string) $flag['tab'],
                'row' => $flag['row'] === null ? null : (int) $flag['row'],
                'field' => (string) $flag['field'],
                'message' => (string) $flag['message'],
            ], $flags),
            'notes' => array_map(fn (array $flag): array => [
                'tab' => (string) $flag['tab'],
                'row' => $flag['row'] === null ? null : (int) $flag['row'],
                'message' => (string) $flag['message'],
            ], $notes),
            'flags_skipped' => $flags === [],
            'skipped_flags' => [],
            'learners_per_class' => $this->learnersPerClass($path, $studentRows),
            'class_teachers_listed' => count($classTeachers),
            'walk' => self::WALK,
            'walk_index' => 0,
            'skipped_steps' => [],
            'results' => [],
            'invites_ready' => 0,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $converted
     * @return array{key: string, rows: list<array<string, mixed>>, counts: array{read: int, accepted: int, flagged: int}}
     */
    private function step(string $key, array $rows, array $converted, string $tab): array
    {
        $counts = $converted['counts'][$tab] ?? ['read' => 0, 'accepted' => 0];

        return [
            'key' => $key,
            'rows' => $rows,
            'counts' => [
                'read' => (int) ($counts['read'] ?? 0),
                'accepted' => (int) ($counts['accepted'] ?? count($rows)),
                'flagged' => $this->flagCount($converted['flags'] ?? [], $tab),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $flags
     */
    private function flagCount(array $flags, string $tab): int
    {
        return count(array_filter(
            $flags,
            fn (array $flag): bool => ($flag['tab'] ?? '') === $tab && ($flag['severity'] ?? 'error') === 'error'
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function summaryCard(array $payload): array
    {
        $steps = [];
        foreach (self::WALK as $key) {
            $counts = $payload['steps'][$key]['counts'];
            $steps[] = [
                'key' => $key,
                'label' => self::TITLES[$key],
                'read' => (int) $counts['read'],
                'accepted' => (int) $counts['accepted'],
                'flagged' => (int) $counts['flagged'],
            ];
        }

        return [
            'kind' => 'summary',
            'school_name' => (string) ($payload['school']['name'] ?? ''),
            'steps' => $steps,
            'flags' => $payload['flags'],
            'notes' => $payload['notes'],
            'class_teachers_listed' => (int) $payload['class_teachers_listed'],
            'needs_flag_choice' => $payload['flags'] !== [] && ! $payload['flags_skipped'],
        ];
    }

    /**
     * @param  array<string, mixed>  $converted
     * @return array<string, mixed>
     */
    private function refusalCard(array $converted): array
    {
        $dpa = $converted['dpa'] ?? [];

        return [
            'kind' => 'refused',
            'tab' => (string) ($dpa['tab'] ?? 'Confirmations'),
            'row' => $dpa['row'] ?? null,
            'message' => (string) ($converted['blocked'] ?? 'The data processing agreement is not confirmed.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{create: int, exists: int, conflict: int, examples: list<array{label: string, bucket: string}>, conflicts: list<array{label: string, message: string}>, creates: list<array<string, mixed>>}
     */
    private function previewStep(School $school, array $payload, string $key): array
    {
        $rows = $payload['steps'][$key]['rows'];
        $create = 0;
        $exists = 0;
        $conflict = 0;
        $examples = [];
        $conflicts = [];
        $creates = [];

        $record = function (string $label, string $bucket, ?string $message = null) use (&$create, &$exists, &$conflict, &$examples, &$conflicts): void {
            if ($bucket === 'create') {
                $create++;
            } elseif ($bucket === 'exists') {
                $exists++;
            } else {
                $conflict++;
                $conflicts[] = ['label' => $label, 'message' => $message ?: 'Conflicts with a row already stored.'];
            }
            if (count($examples) < 3) {
                $examples[] = ['label' => $label, 'bucket' => $bucket];
            }
        };

        if ($key === 'terms') {
            $year = $this->year($school);
            $wanted = array_values(array_unique(array_map(fn (array $row): string => $row['year'], $rows)));
            if ($year instanceof AcademicYear && $wanted !== [] && ! in_array($year->name, $wanted, true)) {
                $record('Academic year '.$year->name, 'conflict', 'This school already has a different academic year.');
            }
            foreach ($rows as $row) {
                $label = $row['name'].' ('.$row['start'].' to '.$row['end'].')';
                $term = AcademicTerm::query()->where('school_id', $school->id)->where('name', $row['name'])->first();
                if (! $term) {
                    $record($label, 'create');
                    $creates[] = $row;
                    continue;
                }
                $same = $this->sameDay($term->starts_on, $row['start']) && $this->sameDay($term->ends_on, $row['end']);
                $record($label, $same ? 'exists' : 'conflict', $same ? null : 'Stored term dates differ from the checklist.');
            }

            return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
        }

        if ($key === 'standards') {
            if (! $this->year($school) instanceof AcademicYear) {
                $record('Academic year', 'conflict', 'Confirm academic terms first so the classes have a year.');

                return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
            }
            $year = $this->year($school);
            foreach ($rows as $row) {
                $label = trim($row['name'].' '.$row['stream']);
                $sectionName = OnboardingEngine::composeClassAndStream($row['name'], $row['stream']);
                $section = $this->section($school, $sectionName);
                $link = $section ? $this->link($school, $year, $section) : null;
                if ($section && $link) {
                    $record($label, 'exists');
                    continue;
                }
                $record($label, 'create');
                $creates[] = $row;
            }

            return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
        }

        if ($key === 'subjects') {
            $year = $this->year($school);
            foreach ($rows as $row) {
                if ($row['classes'] === []) {
                    $record($row['name'], 'conflict', 'No class on this subject matches a class in the checklist.');
                    continue;
                }
                $missing = false;
                $present = true;
                foreach ($row['classes'] as $className) {
                    $links = $year ? $this->engine->standardLinksForClass($school, $year, $className) : collect();
                    if ($links->isEmpty()) {
                        $missing = true;
                        $present = false;
                        break;
                    }
                    foreach ($links as $link) {
                        $found = Subject::query()
                            ->where('school_id', $school->id)
                            ->where('academic_year_id', $year->id)
                            ->where('section_id', $link->section_id)
                            ->whereRaw('LOWER(name) = ?', [$this->key($row['name'])])
                            ->exists();
                        $present = $present && $found;
                    }
                }
                if ($missing) {
                    $record($row['name'], 'conflict', 'A class for this subject is not in the school yet.');
                    continue;
                }
                if ($present) {
                    $record($row['name'], 'exists');
                    continue;
                }
                $record($row['name'], 'create');
                $creates[] = $row;
            }

            return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
        }

        if ($key === 'teachers') {
            $year = $this->year($school);
            foreach ($rows as $row) {
                $label = $row['name'];
                if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $record($label, 'conflict', 'This teacher has no usable email address.');
                    continue;
                }
                $email = strtolower($row['email']);
                $here = User::query()
                    ->where('school_id', $school->id)
                    ->where('usergroup_id', 5)
                    ->where('status', 'active')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first();
                $elsewhere = User::withTrashed()
                    ->where('school_id', '!=', $school->id)
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->exists();
                if ($elsewhere && ! $here) {
                    $record($label, 'conflict', 'That email is already used by someone at another school.');
                    continue;
                }
                $resolved = [];
                $unresolved = false;
                foreach ($row['links'] as $link) {
                    $match = $year ? $this->resolveLink($school, $year, $link) : null;
                    if ($match === null) {
                        $unresolved = true;
                        break;
                    }
                    $resolved[] = $match;
                }
                if ($unresolved) {
                    $record($label, 'conflict', 'A teaching assignment does not match a class and subject already stored.');
                    continue;
                }
                if ($here) {
                    $allLinked = true;
                    foreach ($resolved as $match) {
                        $linked = Teacherlink::query()
                            ->where('school_id', $school->id)
                            ->where('teacher_id', $here->id)
                            ->where('standardLink_id', $match['standardLink_id'])
                            ->where('subject_id', $match['subject_id'])
                            ->exists();
                        $allLinked = $allLinked && $linked;
                    }
                    if ($allLinked) {
                        $record($label, 'exists');
                        continue;
                    }
                    $record($label, 'conflict', 'This teacher is already stored, and a new assignment cannot be added in this step.');
                    continue;
                }
                $record($label, 'create');
                $row['resolved_links'] = $resolved;
                $creates[] = $row;
            }

            return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
        }

        $year = $this->year($school);
        foreach ($rows as $row) {
            $label = trim($row['name'].' · '.$row['class'].' '.$row['stream']);
            $existing = $this->existingStudent($school, $row);
            if ($existing === 'conflict') {
                $record($label, 'conflict', 'More than one stored learner matches this row.');
                continue;
            }
            if ($existing === 'exists') {
                $record($label, 'exists');
                continue;
            }
            $sectionName = OnboardingEngine::composeClassAndStream($row['class'], $row['stream']);
            $section = $this->section($school, $sectionName);
            $link = ($section && $year) ? $this->link($school, $year, $section) : null;
            if (! $link) {
                $record($label, 'conflict', 'That class is not in the school yet.');
                continue;
            }
            $record($label, 'create');
            $creates[] = $row;
        }

        return compact('create', 'exists', 'conflict', 'examples', 'conflicts', 'creates');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $creates
     */
    private function writeStep(School $school, array $payload, string $key, array $creates): void
    {
        $year = $this->ensureYear($school, $payload);

        if ($key === 'terms') {
            $this->engine->saveTerms($school, $year, array_map(fn (array $row): array => [
                'name' => $row['name'],
                'start' => $row['start'],
                'end' => $row['end'],
            ], $creates));

            return;
        }

        if ($key === 'standards') {
            $grouped = [];
            foreach ($creates as $row) {
                $grouped[$row['name']]['name'] = $row['name'];
                if ($row['stream'] !== '') {
                    $grouped[$row['name']]['streams'][$row['stream']] = $row['stream'];
                }
            }
            $classes = [];
            foreach ($grouped as $class) {
                $classes[] = [
                    'name' => $class['name'],
                    'streams' => array_values($class['streams'] ?? []),
                ];
            }
            $this->engine->saveStandards($school, $year, $classes);

            return;
        }

        if ($key === 'subjects') {
            $byClass = [];
            foreach ($creates as $row) {
                foreach ($row['classes'] as $className) {
                    $byClass[$className][$this->key($row['name'])] = $row['name'];
                }
            }
            $payloadForEngine = [];
            foreach ($byClass as $className => $names) {
                $payloadForEngine[$className] = array_values($names);
            }
            $this->engine->saveSubjects($school, $year, $payloadForEngine);

            return;
        }

        if ($key === 'teachers') {
            $drafts = [];
            foreach ($creates as $row) {
                $drafts[] = [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'links' => $row['resolved_links'],
                ];
            }
            $result = $this->engine->saveTeachers($school, $year, $drafts);
            if (($result['skipped'] ?? []) !== []) {
                throw ValidationException::withMessages([
                    'teachers' => 'A teacher row was not created, so this step was rolled back.',
                ]);
            }

            return;
        }

        $drafts = [];
        foreach ($creates as $row) {
            $drafts[] = [
                'name' => $row['name'],
                'class' => $row['class'],
                'stream' => $row['stream'],
                'parent_name' => $row['parent_name'],
                'parent_phone' => $row['parent_phone'],
                'parent_email' => $row['parent_email'],
                'gender' => $row['gender'],
                'date_of_birth' => $row['date_of_birth'],
                'school_student_id' => $row['school_student_id'],
            ];
        }
        $result = $this->engine->saveStudents($school, $year, $drafts);
        if (($result['skipped'] ?? []) !== []) {
            throw ValidationException::withMessages([
                'students' => 'A learner row was not created, so this step was rolled back.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureYear(School $school, array $payload): AcademicYear
    {
        $existing = $this->year($school);
        if ($existing instanceof AcademicYear) {
            return $existing;
        }

        $rows = $payload['steps']['terms']['rows'];
        $name = (string) ($rows[0]['year'] ?? now()->year);
        $starts = array_values(array_filter(array_column($rows, 'start')));
        $ends = array_values(array_filter(array_column($rows, 'end')));
        sort($starts);
        rsort($ends);

        return $this->engine->saveAcademicYear(
            $school,
            $name,
            $starts[0] ?? null,
            $ends[0] ?? null,
            'Current Academic Year'
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{create: int, exists: int}  $preview
     * @return array<string, mixed>
     */
    private function resultCard(School $school, array $payload, string $key, array $preview): array
    {
        $link = $this->pageLink($school, $payload, $key);
        $next = $this->currentKey($payload);

        return [
            'kind' => $next === null ? 'done' : 'result',
            'title' => self::TITLES[$key],
            'created' => $preview['create'],
            'existed' => $preview['exists'],
            'href' => $link['href'],
            'href_label' => $link['label'],
            'completion' => $next === null ? $this->completionCard($school, $payload) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function completionCard(School $school, array $payload): array
    {
        $year = $this->year($school);
        $checks = [];
        $ok = true;
        foreach ($payload['learners_per_class'] as $expected) {
            $sectionName = OnboardingEngine::composeClassAndStream($expected['class'], $expected['stream']);
            $actual = 0;
            if ($year instanceof AcademicYear) {
                $section = $this->section($school, $sectionName);
                $link = $section ? $this->link($school, $year, $section) : null;
                if ($link) {
                    $actual = StudentAcademic::query()
                        ->where('school_id', $school->id)
                        ->where('academic_year_id', $year->id)
                        ->where('standardLink_id', $link->id)
                        ->whereHas('user', fn ($query) => $query->where('school_id', $school->id)->where('usergroup_id', 6)->where('status', 'active'))
                        ->count();
                }
            }
            $match = $actual === (int) $expected['count'];
            $ok = $ok && $match;
            $checks[] = [
                'label' => trim($expected['class'].' '.$expected['stream']),
                'expected' => (int) $expected['count'],
                'actual' => $actual,
                'match' => $match,
            ];
        }

        $academics = StudentAcademic::query()->where('school_id', $school->id)->whereHas('user', fn ($query) => $query->where('status', 'active')->where('usergroup_id', 6))->get();
        $missingKls = $academics->filter(fn (StudentAcademic $row): bool => ! StudentIdGeneratorService::isValid((string) $row->klassapp_student_id))->count();
        $phones = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 7)
            ->where('status', 'active')
            ->whereNotNull('mobile_no')
            ->pluck('mobile_no');
        $badPhones = $phones->filter(fn ($phone): bool => preg_match('/^\+[1-9]\d{7,14}$/', (string) $phone) !== 1)->count();

        return [
            'kind' => 'done',
            'checks' => $checks,
            'checks_ok' => $ok,
            'kls_present' => $academics->count() > 0 && $missingKls === 0,
            'kls_missing' => $missingKls,
            'phones_e164' => $badPhones === 0,
            'phones_bad' => $badPhones,
            'invites_ready' => (int) ($payload['invites_ready'] ?? 0),
            'class_teachers_listed' => (int) ($payload['class_teachers_listed'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{href: string, label: string}
     */
    private function pageLink(School $school, array $payload, string $key): array
    {
        if ($key === 'terms') {
            return ['href' => url('/admin/academic-term'), 'label' => 'Open academic terms'];
        }
        if ($key === 'standards') {
            return ['href' => url('/admin/standards'), 'label' => 'Open classes'];
        }
        if ($key === 'subjects') {
            return ['href' => url('/admin/subjects'), 'label' => 'Open subjects'];
        }
        if ($key === 'teachers') {
            return ['href' => url('/admin/teachers'), 'label' => 'Open teachers'];
        }

        $year = $this->year($school);
        $first = $payload['steps']['students']['rows'][0] ?? null;
        $href = url('/admin/students');
        if (is_array($first) && $year instanceof AcademicYear) {
            $section = $this->section($school, OnboardingEngine::composeClassAndStream($first['class'], $first['stream']));
            $link = $section ? $this->link($school, $year, $section) : null;
            if ($link) {
                $href = url('/admin/students?standard='.$link->id);
            }
        }

        return ['href' => $href, 'label' => 'Open the students list'];
    }

    /**
     * @param  list<array<string, mixed>>  $studentRows
     * @return list<array{class: string, stream: string, count: int}>
     */
    private function learnersPerClass(string $path, array $studentRows): array
    {
        try {
            $book = IOFactory::load($path);
        } catch (\Throwable) {
            return $this->countsFromRows($studentRows);
        }

        $sheet = null;
        foreach ($book->getWorksheetIterator() as $candidate) {
            if (strcasecmp(trim($candidate->getTitle()), 'Learners per Class') === 0) {
                $sheet = $candidate;
                break;
            }
        }
        if ($sheet === null) {
            return $this->countsFromRows($studentRows);
        }

        $rows = [];
        foreach ($sheet->toArray(null, true, false, false) as $index => $row) {
            if ($index < 2) {
                continue;
            }
            $class = trim((string) ($row[0] ?? ''));
            if ($class === '') {
                continue;
            }
            if (! is_numeric($row[2] ?? null)) {
                continue;
            }
            $rows[] = [
                'class' => $class,
                'stream' => trim((string) ($row[1] ?? '')),
                'count' => (int) $row[2],
            ];
        }

        return $rows === [] ? $this->countsFromRows($studentRows) : $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $studentRows
     * @return list<array{class: string, stream: string, count: int}>
     */
    private function countsFromRows(array $studentRows): array
    {
        $counts = [];
        foreach ($studentRows as $row) {
            $key = $row['class'].'|'.$row['stream'];
            $counts[$key]['class'] = $row['class'];
            $counts[$key]['stream'] = $row['stream'];
            $counts[$key]['count'] = ($counts[$key]['count'] ?? 0) + 1;
        }

        return array_values($counts);
    }

    /**
     * @param  list<string>  $classNames
     * @return list<string>
     */
    private function classesForSubject(string $raw, array $classNames): array
    {
        $raw = trim($raw);
        if ($raw === '' || strcasecmp($raw, 'all') === 0 || strcasecmp($raw, 'all classes') === 0) {
            return $classNames;
        }

        $matched = [];
        foreach (preg_split('/[,;]/', $raw) ?: [] as $part) {
            foreach ($classNames as $className) {
                if (strcasecmp($className, trim((string) $part)) === 0) {
                    $matched[$className] = $className;
                }
            }
        }

        return array_values($matched);
    }

    /**
     * @param  array{class: string, stream: string, subject: string}  $link
     * @return array{standardLink_id: int, subject_id: int}|null
     */
    private function resolveLink(School $school, AcademicYear $year, array $link): ?array
    {
        $section = $this->section($school, OnboardingEngine::composeClassAndStream($link['class'], $link['stream']));
        if (! $section) {
            return null;
        }
        $standardLink = $this->link($school, $year, $section);
        if (! $standardLink) {
            return null;
        }
        $subject = Subject::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $section->id)
            ->whereRaw('LOWER(name) = ?', [$this->key($link['subject'])])
            ->first();
        if (! $subject) {
            return null;
        }

        return [
            'standardLink_id' => (int) $standardLink->id,
            'subject_id' => (int) $subject->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function existingStudent(School $school, array $row): string
    {
        if ($row['school_student_id'] !== '') {
            $count = StudentAcademic::query()
                ->where('school_id', $school->id)
                ->where('school_student_id', $row['school_student_id'])
                ->count();
            if ($count > 1) {
                return 'conflict';
            }
            if ($count === 1) {
                return 'exists';
            }
        }

        $section = $this->section($school, OnboardingEngine::composeClassAndStream($row['class'], $row['stream']));
        if (! $section) {
            return 'create';
        }
        $count = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereRaw('LOWER(name) = ?', [$this->key($row['name'])])
            ->whereHas('studentAcademic', function ($query) use ($school, $section): void {
                $query->where('student_academics.school_id', $school->id)
                    ->whereIn('standardLink_id', StandardLink::query()
                        ->where('school_id', $school->id)
                        ->where('section_id', $section->id)
                        ->select('id'));
            })
            ->count();

        return $count > 1 ? 'conflict' : ($count === 1 ? 'exists' : 'create');
    }

    private function section(School $school, string $name): ?Section
    {
        return Section::query()
            ->where('school_id', $school->id)
            ->where('name', $name)
            ->first();
    }

    private function link(School $school, AcademicYear $year, Section $section): ?StandardLink
    {
        return StandardLink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $section->id)
            ->where('status', '1')
            ->first();
    }

    private function year(School $school): ?AcademicYear
    {
        return AcademicYear::query()->where('school_id', $school->id)->orderByDesc('id')->first();
    }

    private function sameDay(mixed $stored, string $wanted): bool
    {
        if ($stored === null || $wanted === '') {
            return $stored === null && $wanted === '';
        }

        try {
            return \Carbon\Carbon::parse($stored)->toDateString() === \Carbon\Carbon::parse($wanted)->toDateString();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<array{tab: string, row: ?int}>  $flags
     */
    private function rowIsFlagged(array $flags, string $tab, int $row): bool
    {
        foreach ($flags as $flag) {
            if ($flag['tab'] === $tab && (int) $flag['row'] === $row) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function errorFlags(array $payload): array
    {
        return array_values($payload['flags'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function currentKey(array $payload): ?string
    {
        $index = (int) ($payload['walk_index'] ?? 0);
        $walk = $payload['walk'] ?? self::WALK;

        return $walk[$index] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireDpa(OnboardingPlan $plan): array
    {
        $payload = $plan->payload;
        if (! is_array($payload) || (int) ($payload['schema'] ?? 0) !== self::SCHEMA) {
            throw ValidationException::withMessages(['plan' => 'This checklist plan cannot be used.']);
        }
        if (($payload['dpa']['confirmed'] ?? false) !== true || $plan->dpa_confirmed_at === null) {
            throw ValidationException::withMessages(['plan' => 'The data processing agreement is not confirmed.']);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireReady(OnboardingPlan $plan): array
    {
        $payload = $this->requireDpa($plan);
        if ($plan->status === 'abandoned') {
            throw ValidationException::withMessages(['plan' => 'This checklist was stopped. Upload the workbook again.']);
        }
        if (($payload['flags'] ?? []) !== [] && ! ($payload['flags_skipped'] ?? false)) {
            throw ValidationException::withMessages(['plan' => 'Choose what to do with the flagged rows first.']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function savePayload(OnboardingPlan $plan, array $payload, string $status): void
    {
        $plan->payload = $payload;
        $plan->status = $status;
        $plan->save();
    }

    private function fresh(OnboardingPlan $plan, User $actor): OnboardingPlan
    {
        $this->assertActor($plan->school, $actor);
        if ((int) $plan->school_id !== (int) $actor->school_id) {
            throw ValidationException::withMessages(['plan' => 'That checklist belongs to another school.']);
        }

        return $plan;
    }

    private function assertActor(School $school, User $actor): void
    {
        if (! $this->gate->enabledFor($school)) {
            throw ValidationException::withMessages(['plan' => 'Checklist onboarding is not on for this school.']);
        }
        if ((int) $actor->school_id !== (int) $school->id || (int) $actor->usergroup_id !== 3) {
            throw ValidationException::withMessages(['plan' => 'Only a school admin of this school can onboard from a checklist.']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function failedCard(string $message): array
    {
        return ['kind' => 'failed', 'message' => $message];
    }

    private function key(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }
}
