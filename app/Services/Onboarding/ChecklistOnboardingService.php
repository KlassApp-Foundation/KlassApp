<?php

namespace App\Services\Onboarding;

use App\Helpers\WhatsAppPhoneHelper;
use App\Mail\TeacherInviteLinkMail;
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
use App\Services\TeacherInviteLinkService;
use App\Support\PhoneCountry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
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

    private const STEP_NAMES = [
        'terms' => 'Terms',
        'standards' => 'Classes and streams',
        'subjects' => 'Subjects',
        'teachers' => 'Teachers and assignments',
        'students' => 'Learners and parents',
    ];

    /** @var array<string, string> */
    private const SOURCE_TABS = [
        'terms' => 'Terms & Exams',
        'standards' => 'Classes & Streams',
        'subjects' => 'Subjects',
        'teachers' => 'Teachers',
        'students' => 'Learners',
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private const NOUNS = [
        'terms' => ['term', 'terms'],
        'standards' => ['class', 'classes'],
        'subjects' => ['subject', 'subjects'],
        'teachers' => ['teacher', 'teachers'],
        'students' => ['learner', 'learners'],
    ];

    public function __construct(
        private ChecklistWorkbookConverter $converter,
        private ChecklistOnboardingGate $gate,
        private OnboardingEngine $engine,
    ) {}

    /**
     * @return array{ok: bool, code: string, plan_id?: int, card: array<string, mixed>}
     */
    public function ingest(School $school, User $actor, string $path, string $originalName, ?callable $progress = null): array
    {
        $this->assertActor($school, $actor);
        if (! is_file($path)) {
            return ['ok' => false, 'code' => 'unreadable', 'card' => $this->failedCard('The checklist file could not be read.')];
        }

        try {
            $converted = $this->converter->convert($path, $progress);
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
        $index = array_search($key, self::WALK, true);

        return [
            'kind' => 'preview',
            'step_key' => $key,
            'title' => self::TITLES[$key],
            'name' => self::STEP_NAMES[$key],
            'step_number' => is_int($index) ? $index + 1 : 1,
            'step_total' => count(self::WALK),
            'create' => $preview['create'],
            'exists' => $preview['exists'],
            'conflict' => $preview['conflict'],
            'skipped' => count($payload['skipped_flags'] ?? []),
            'source_tab' => self::SOURCE_TABS[$key],
            'proposal' => $this->proposalFor($key),
            'approve_label' => $this->approveLabel($key, $preview['create'], $preview['creates'] ?? []),
            'question' => $this->questionFor($payload, $key, $preview),
            'examples' => array_slice($preview['examples'], 0, 8),
            'example_total' => $preview['create'] + $preview['exists'] + $preview['conflict'],
            'conflicts' => array_slice($preview['conflicts'], 0, 3),
            'receipt' => $this->receipt($key, $preview, $payload, 'proposal'),
        ];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function apply(OnboardingPlan $plan, User $actor, ?callable $progress = null): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireReady($plan);
        $key = $this->currentKey($payload);
        if ($key === null) {
            return ['ok' => true, 'code' => 'done', 'card' => $this->completionCard($plan->school, $payload)];
        }

        if ($progress) {
            $progress([
                'step' => $key,
                'line' => 'check-'.$key,
                'label' => 'Checking '.self::STEP_NAMES[$key],
                'status' => 'running',
                'result' => null,
            ]);
        }
        $preview = $this->previewStep($plan->school, $payload, $key);
        if ($preview['conflict'] > 0) {
            if ($progress) {
                $progress([
                    'step' => $key,
                    'line' => 'check-'.$key,
                    'label' => 'Checking '.self::STEP_NAMES[$key],
                    'status' => 'problem',
                    'result' => $preview['conflict'].($preview['conflict'] === 1 ? ' conflict' : ' conflicts'),
                ]);
            }

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

        if ($progress) {
            $progress([
                'step' => $key,
                'line' => 'check-'.$key,
                'label' => 'Checking '.self::STEP_NAMES[$key],
                'status' => 'done',
                'result' => 'no conflicts',
            ]);
        }
        $this->emitRead($progress, $key, $payload);

        $beforeInvites = TeacherInvite::query()->where('school_id', $plan->school_id)->count();
        $tallies = [];
        $this->engine->listenForCreates(function (array $event) use ($progress, &$tallies): void {
            $id = (string) ($event['line'] ?? '');
            if ($id === '') {
                return;
            }
            $tallies[$id] = [
                'step' => (string) ($event['step'] ?? ''),
                'label' => (string) ($event['label'] ?? ''),
                'count' => ($tallies[$id]['count'] ?? 0) + 1,
            ];
            if ($progress) {
                $progress([
                    'step' => $tallies[$id]['step'],
                    'line' => $id,
                    'label' => $tallies[$id]['label'],
                    'status' => 'running',
                    'result' => (string) $tallies[$id]['count'],
                ]);
            }
        });
        try {
            DB::transaction(function () use ($plan, $payload, $key, $preview, $progress): void {
                ChecklistInviteHold::hold(function () use ($plan, $payload, $key, $preview, $progress): void {
                    if ($preview['create'] > 0) {
                        $this->writeStep($plan->school, $payload, $key, $preview['creates'], $progress);
                    }
                });
            });
        } catch (ValidationException $e) {
            Log::warning('Checklist onboarding step refused', [
                'school_id' => $plan->school_id,
                'step' => $key,
                'exception' => $e::class,
            ]);

            $message = collect($e->errors())->flatten()->first()
                ?: 'This step was not applied. Nothing from this step was saved.';
            if ($progress) {
                $progress([
                    'step' => $key,
                    'line' => 'failed',
                    'label' => self::STEP_NAMES[$key],
                    'status' => 'problem',
                    'result' => $message,
                ]);
            }

            return [
                'ok' => false,
                'code' => 'failed',
                'card' => [
                    'kind' => 'failed',
                    'title' => self::TITLES[$key],
                    'message' => $message,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('Checklist onboarding step failed', [
                'school_id' => $plan->school_id,
                'step' => $key,
                'exception' => $e::class,
            ]);

            if ($progress) {
                $progress([
                    'step' => $key,
                    'line' => 'failed',
                    'label' => self::STEP_NAMES[$key],
                    'status' => 'problem',
                    'result' => 'This step was not applied. Nothing from this step was saved.',
                ]);
            }

            return [
                'ok' => false,
                'code' => 'failed',
                'card' => [
                    'kind' => 'failed',
                    'title' => self::TITLES[$key],
                    'message' => 'This step was not applied. Nothing from this step was saved.',
                ],
            ];
        } finally {
            $this->engine->listenForCreates(null);
        }

        if ($progress) {
            foreach ($tallies as $id => $info) {
                $progress([
                    'step' => $info['step'],
                    'line' => $id,
                    'label' => $info['label'],
                    'status' => 'done',
                    'result' => $info['count'].' of '.$info['count'],
                ]);
            }
        }

        $inviteDelta = max(0, TeacherInvite::query()->where('school_id', $plan->school_id)->count() - $beforeInvites);
        if ($progress && in_array($key, ['teachers', 'students'], true)) {
            $progress([
                'step' => $key,
                'line' => 'invites',
                'label' => 'Preparing invites',
                'status' => 'done',
                'result' => $inviteDelta.' ready, not sent',
            ]);
        }

        $payload['invites_ready'] = (int) ($payload['invites_ready'] ?? 0) + $inviteDelta;
        $payload['results'][$key] = [
            'created' => $preview['create'],
            'existed' => $preview['exists'],
            'invites' => $inviteDelta,
        ];
        $payload['walk_index'] = (int) $payload['walk_index'] + 1;
        $status = $this->currentKey($payload) === null ? 'applied' : 'applying';
        $card = $this->resultCard($plan->school, $payload, $key, $preview);
        $view = $card;
        if (($view['kind'] ?? '') === 'done') {
            $view['kind'] = 'result';
            $view['completion'] = null;
        }
        $payload['results'][$key]['view'] = $view;
        $this->savePayload($plan, $payload, $status);

        return ['ok' => true, 'code' => 'result', 'card' => $card];
    }

    /**
     * The result card stored when the step was applied. Reading it writes nothing.
     *
     * @return array<string, mixed>|null
     */
    public function storedResult(OnboardingPlan $plan, User $actor, string $key): ?array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $view = $payload['results'][$key]['view'] ?? null;
        if (! is_array($view) || ($view['kind'] ?? '') !== 'result') {
            return null;
        }

        return $view;
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
        $assignmentFlags = $this->flaggedRowCount($converted['flags'] ?? [], 'Teaching Assignments');
        if ($assignmentFlags > 0) {
            $steps['teachers']['counts']['read'] += $assignmentFlags;
            $steps['teachers']['counts']['flagged'] += $assignmentFlags;
            $steps['teachers']['counts']['accepted'] = $steps['teachers']['counts']['read'] - $steps['teachers']['counts']['flagged'];
        }

        return [
            'schema' => self::SCHEMA,
            'source' => [
                'name' => $originalName,
                'sha256' => hash_file('sha256', $path) ?: '',
            ],
            'school' => [
                'name' => (string) ($converted['school'] ?? ''),
                'country' => (string) ($converted['schoolCountry'] ?? ''),
                'curriculum' => (string) ($converted['schoolCurriculum'] ?? ''),
                'whatsapp' => (string) ($converted['schoolWhatsapp'] ?? ''),
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
            'class_teachers' => $classTeachers,
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
        $counts = $converted['counts'][$tab] ?? ['read' => 0];
        $flagged = $this->flaggedRowCount($converted['flags'] ?? [], $tab);
        $read = max((int) ($counts['read'] ?? 0), $flagged);

        return [
            'key' => $key,
            'rows' => $rows,
            'counts' => [
                'read' => $read,
                'accepted' => $read - $flagged,
                'flagged' => $flagged,
            ],
        ];
    }

    /**
     * Distinct error rows on one tab. A row with two errors counts once, so Read = Ready + Flagged.
     *
     * @param  list<array<string, mixed>>  $flags
     */
    private function flaggedRowCount(array $flags, string $tab): int
    {
        $rows = [];
        $withoutRow = false;
        foreach ($flags as $flag) {
            if (($flag['tab'] ?? '') !== $tab || ($flag['severity'] ?? 'error') !== 'error') {
                continue;
            }
            if (($flag['row'] ?? null) === null) {
                $withoutRow = true;

                continue;
            }
            $rows[(int) $flag['row']] = true;
        }

        return count($rows) + ($withoutRow ? 1 : 0);
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
            'stats' => $this->statTiles($this->leavingFlaggedRowsOut($payload)),
            'flags' => array_map(function (array $flag) use ($payload): array {
                return $this->presentFlag($flag, $payload);
            }, $payload['flags']),
            'notes' => $payload['notes'],
            'class_teachers_listed' => (int) $payload['class_teachers_listed'],
            'needs_flag_choice' => $payload['flags'] !== [] && ! $payload['flags_skipped'],
            'core_gaps' => $this->coreGaps($steps),
        ];
    }

    /**
     * Core lists have to come from the checklist. An empty one is said plainly.
     *
     * @param  list<array{key: string, label: string, accepted: int}>  $steps
     * @return list<string>
     */
    private function coreGaps(array $steps): array
    {
        $core = [
            'standards' => 'Classes and streams',
            'subjects' => 'Subjects',
            'teachers' => 'Teachers and assignments',
            'students' => 'Learners and parents',
        ];
        $gaps = [];
        foreach ($steps as $step) {
            $name = $core[$step['key']] ?? null;
            if ($name !== null && (int) $step['accepted'] < 1) {
                $gaps[] = $name.' must come from the checklist. This list is empty. Fix the workbook.';
            }
        }

        return $gaps;
    }

    /**
     * Replace the term rows on the plan. Does not write the school.
     *
     * @param  list<array<string, mixed>>  $terms
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function replaceTermRows(OnboardingPlan $plan, User $actor, array $terms): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = $this->requireReady($plan);
        $existing = $payload['steps']['terms']['rows'] ?? [];
        $rows = [];
        foreach (array_values($terms) as $index => $term) {
            if (! is_array($term)) {
                continue;
            }
            $name = trim((string) ($term['name'] ?? ''));
            $start = substr(trim((string) ($term['start'] ?? '')), 0, 10);
            $end = substr(trim((string) ($term['end'] ?? '')), 0, 10);
            if ($name === '' || $start === '' || $end === '') {
                throw ValidationException::withMessages(['termName' => 'Add at least one academic term.']);
            }
            $prior = is_array($existing[$index] ?? null) ? $existing[$index] : [];
            $rows[] = array_merge($prior, [
                'name' => $name,
                'start' => $start,
                'end' => $end,
            ]);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['termName' => 'Add at least one academic term.']);
        }

        $payload['steps']['terms']['rows'] = $rows;
        if (isset($payload['steps']['terms']['counts']) && is_array($payload['steps']['terms']['counts'])) {
            $payload['steps']['terms']['counts']['accepted'] = count($rows);
            $payload['steps']['terms']['counts']['read'] = max(
                (int) ($payload['steps']['terms']['counts']['read'] ?? 0),
                count($rows)
            );
        }
        $plan->payload = $payload;
        $plan->save();

        return [
            'ok' => true,
            'code' => 'preview',
            'card' => $this->previewCard($plan->fresh(), $actor),
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
            if (count($examples) < 8) {
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
    private function writeStep(School $school, array $payload, string $key, array $creates, ?callable $progress = null): void
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
            if ($progress) {
                $progress([
                    'step' => 'standards',
                    'line' => 'classes',
                    'label' => 'Creating classes',
                    'status' => 'running',
                    'result' => null,
                ]);
            }
            $this->engine->saveStandards($school, $year, $classes);
            if ($progress) {
                $progress([
                    'step' => 'standards',
                    'line' => 'classes',
                    'label' => 'Creating classes',
                    'status' => 'done',
                    'result' => count($classes).' of '.count($classes),
                ]);
                $streams = 0;
                foreach ($classes as $class) {
                    $streams += count($class['streams'] ?? []);
                }
                if ($streams > 0) {
                    $progress([
                        'step' => 'standards',
                        'line' => 'streams',
                        'label' => 'Creating streams',
                        'status' => 'done',
                        'result' => $streams.' of '.$streams,
                    ]);
                }
            }

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
            'step_key' => $key,
            'source_tab' => self::SOURCE_TABS[$key] ?? '',
            'name' => self::STEP_NAMES[$key],
            'title' => self::TITLES[$key],
            'created' => $preview['create'],
            'existed' => $preview['exists'],
            'line' => $this->doneLine($key, $preview['create'], $preview['creates'] ?? []),
            'href' => $link['href'],
            'href_label' => $link['label'],
            'receipt' => $this->receipt($key, $preview, $payload, 'result'),
            'invites' => (int) ($payload['results'][$key]['invites'] ?? 0),
            'completion' => $next === null ? $this->completionCard($school, $payload) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teacherInvitesCard(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $teachers = [];
        $blocked = false;
        $ready = 0;
        foreach ($payload['steps']['teachers']['rows'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $email = trim((string) ($row['email'] ?? ''));
            $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $classes = [];
            foreach ($row['links'] ?? [] as $link) {
                if (! is_array($link)) {
                    continue;
                }
                $label = trim((string) ($link['class'] ?? '').' '.(string) ($link['stream'] ?? ''));
                if ($label !== '') {
                    $classes[$label] = $label;
                }
            }
            $classList = implode(', ', array_values($classes));
            $name = trim((string) ($row['name'] ?? ''));
            $shownEmail = $email !== '' ? $email : 'no email';
            $line = $name.', '.$shownEmail.($classList !== '' ? ', '.$classList : '');
            if (! $valid) {
                $blocked = true;
            } else {
                $ready++;
            }
            $teachers[] = [
                'index' => (int) $index,
                'name' => $name,
                'email' => $email,
                'line' => $line,
                'valid' => $valid,
            ];
        }

        return [
            'kind' => 'preview',
            'step_key' => 'teacher_invites',
            'name' => 'Teacher invites',
            'title' => 'Teacher invites',
            'proposal' => 'Each teacher is listed with the email and the classes from the checklist.',
            'statement' => true,
            'approve_label' => $ready === 1 ? 'Send 1 invite' : 'Send '.$ready.' invites',
            'teachers' => $teachers,
            'emails_blocked' => $blocked,
            'ready' => $ready,
            'source_tab' => 'Teachers',
        ];
    }

    /**
     * @return array{ok: bool, code: string, card: array<string, mixed>}
     */
    public function sendTeacherInvites(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        if (array_key_exists('teacher_invites_sent', $payload)) {
            return ['ok' => true, 'code' => 'result', 'card' => $this->teacherInviteResult($payload)];
        }

        $sent = 0;
        $missing = 0;
        $captured = '';
        $school = $plan->school;
        foreach ($payload['steps']['teachers']['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $email = trim((string) ($row['email'] ?? ''));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            $teacher = $this->teacherByEmail((int) $school->id, $email);
            if ($teacher === null) {
                $missing++;

                continue;
            }
            $classes = [];
            foreach ($row['links'] ?? [] as $link) {
                if (! is_array($link)) {
                    continue;
                }
                $label = trim((string) ($link['class'] ?? '').' '.(string) ($link['stream'] ?? ''));
                if ($label !== '') {
                    $classes[$label] = $label;
                }
            }
            $className = implode(', ', array_values($classes));
            $existing = TeacherInvite::query()
                ->where('school_id', $school->id)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->whereNull('claimed_at')
                ->whereNull('revoked_at')
                ->orderByDesc('id')
                ->first();
            $issued = $existing
                ? TeacherInviteLinkService::reissue($existing)
                : TeacherInviteLinkService::issue($school, $email, (string) $teacher->name, null, (string) ($row['phone'] ?? ''));
            $invite = $issued['invite'];
            $invite->user_id = $teacher->id;
            $invite->role = 'teacher';
            $invite->save();
            $mailable = new TeacherInviteLinkMail(
                name: (string) ($teacher->name ?: $row['name'] ?? 'Teacher'),
                schoolName: (string) ($school->name ?: 'KlassApp'),
                inviteUrl: TeacherInviteLinkService::inviteUrl($issued['token']),
                className: $className !== '' ? $className : null,
                expiresAt: $invite->expires_at,
            );
            Mail::to($email)->send($mailable);
            if ($captured === '') {
                $preview = new TeacherInviteLinkMail(
                    name: (string) ($teacher->name ?: $row['name'] ?? 'Teacher'),
                    schoolName: (string) ($school->name ?: 'KlassApp'),
                    inviteUrl: TeacherInviteLinkService::inviteUrl($issued['token']),
                    className: $className !== '' ? $className : null,
                    expiresAt: $invite->expires_at,
                );
                $captured = trim(preg_replace('/\s+/', ' ', strip_tags($preview->render())) ?? '');
            }
            $sent++;
        }

        $payload['teacher_invites_sent'] = $sent;
        $payload['teacher_invites_missing'] = $missing;
        $payload['teacher_invite_preview'] = $captured;
        $this->savePayload($plan, $payload, (string) $plan->status);

        return ['ok' => true, 'code' => 'result', 'card' => $this->teacherInviteResult($payload)];
    }

    /**
     * @return array<string, mixed>
     */
    public function saveTeacherEmail(OnboardingPlan $plan, User $actor, int $index, string $email): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $rows = $payload['steps']['teachers']['rows'] ?? [];
        if (! isset($rows[$index]) || ! is_array($rows[$index])) {
            throw ValidationException::withMessages(['inviteEmails.'.$index => 'That teacher is not on this checklist.']);
        }
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['inviteEmails.'.$index => 'Enter a valid email.']);
        }
        $previous = mb_strtolower(trim((string) ($rows[$index]['email'] ?? '')));
        $rows[$index]['email'] = $email;
        $payload['steps']['teachers']['rows'] = array_values($rows);
        $this->savePayload($plan, $payload, (string) $plan->status);
        $teacher = $this->teacherByEmail((int) $plan->school_id, $previous) ?? $this->teacherByEmail((int) $plan->school_id, $email);
        if ($teacher !== null) {
            $teacher->email = $email;
            $teacher->save();
        }

        return $this->teacherInvitesCard($plan->fresh(), $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function whatsappCard(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $raw = trim((string) ($payload['school']['whatsapp'] ?? ''));
        $country = (string) ($payload['school']['country'] ?? '');

        return [
            'kind' => 'preview',
            'step_key' => 'whatsapp',
            'name' => 'WhatsApp',
            'title' => 'WhatsApp',
            'proposal' => 'This is the school WhatsApp number from Admin & Contacts.',
            'statement' => true,
            'approve_label' => 'Connect WhatsApp',
            'phone_display' => $this->formatSchoolPhone($raw, $country),
            'phone_raw' => $raw,
            'phone_hint' => PhoneCountry::hint($country, true),
            'source_tab' => 'Admin & Contacts',
            'show_editor' => false,
            'connected' => false,
            'connect_notice' => '',
        ];
    }

    /**
     * @return array{phone_display: string, phone_raw: string, phone_hint: string}
     */
    public function saveSchoolWhatsapp(OnboardingPlan $plan, User $actor, string $phone, string $country): array
    {
        $plan = $this->fresh($plan, $actor);
        $normalised = PhoneCountry::normalise($phone, $country);
        if ($normalised === null) {
            throw ValidationException::withMessages([
                'whatsappPhone' => PhoneCountry::error($country, true),
            ]);
        }
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $payload['school']['whatsapp'] = $normalised;
        if ($country !== '' && trim((string) ($payload['school']['country'] ?? '')) === '') {
            $payload['school']['country'] = $country;
        }
        $this->savePayload($plan, $payload, (string) $plan->status);

        return [
            'phone_display' => $this->formatSchoolPhone($normalised, (string) ($payload['school']['country'] ?? $country)),
            'phone_raw' => $normalised,
            'phone_hint' => PhoneCountry::hint((string) ($payload['school']['country'] ?? $country), true),
        ];
    }

    public function whatsappServiceReady(): bool
    {
        $token = trim((string) config('services.whatsapp.business_api_token'));
        $phoneId = trim((string) config('services.whatsapp.business_phone_number_id'));
        $secret = trim((string) config('services.whatsapp.app_secret'));

        return $token !== '' && $phoneId !== '' && $secret !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function doneCard(OnboardingPlan $plan, User $actor): array
    {
        $plan = $this->fresh($plan, $actor);
        $payload = is_array($plan->payload) ? $plan->payload : [];

        return $this->completionCard($plan->school, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function teacherInviteResult(array $payload): array
    {
        $sent = (int) ($payload['teacher_invites_sent'] ?? 0);
        $missing = (int) ($payload['teacher_invites_missing'] ?? 0);
        $line = $sent === 1
            ? '1 invite sent (captured on this demo copy)'
            : $sent.' invites sent (captured on this demo copy)';
        if ($missing > 0) {
            $line .= $missing === 1
                ? ' 1 teacher is not in the school yet, so no invite was sent for them.'
                : ' '.$missing.' teachers are not in the school yet, so no invite was sent for them.';
        }

        return [
            'kind' => 'result',
            'step_key' => 'teacher_invites',
            'name' => 'Teacher invites',
            'title' => 'Teacher invites',
            'created' => $sent,
            'line' => $line,
            'href' => '',
            'href_label' => '',
            'receipt' => [],
            'captured_invite' => (string) ($payload['teacher_invite_preview'] ?? ''),
            'source_tab' => 'Teachers',
        ];
    }

    private function teacherByEmail(int $schoolId, string $email): ?User
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $matches = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function formatSchoolPhone(string $raw, string $country): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $e164 = PhoneCountry::normalise($raw, $country);
        if ($e164 === null) {
            $e164 = WhatsAppPhoneHelper::normaliseInternational($raw);
        }
        if ($e164 === null) {
            return $raw;
        }

        try {
            $parsed = PhoneNumberUtil::getInstance()->parse($e164, null);

            return PhoneNumberUtil::getInstance()->format($parsed, PhoneNumberFormat::INTERNATIONAL);
        } catch (\Throwable) {
            return $e164;
        }
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
            'stats' => $this->statTiles($payload),
            'checks' => $checks,
            'checks_ok' => $ok,
            'kls_present' => $academics->count() > 0 && $missingKls === 0,
            'kls_missing' => $missingKls,
            'phones_e164' => $badPhones === 0,
            'phones_bad' => $badPhones,
            'invites_ready' => (int) ($payload['invites_ready'] ?? 0),
            'teacher_invites_sent' => (int) ($payload['teacher_invites_sent'] ?? 0),
            'class_teachers_listed' => (int) ($payload['class_teachers_listed'] ?? 0),
            'skipped' => count($payload['skipped_flags'] ?? []),
            'later' => array_map(function (array $flag) use ($payload): array {
                $shown = $this->presentFlag($flag, $payload);

                return [
                    'tab' => (string) ($shown['tab'] ?? ''),
                    'row' => $shown['row'] ?? null,
                    'field' => (string) ($shown['field'] ?? ''),
                    'value' => (string) ($shown['value'] ?? ''),
                    'message' => (string) ($shown['message'] ?? ''),
                    'fix' => (string) ($shown['fix'] ?? ''),
                ];
            }, array_values($payload['skipped_flags'] ?? [])),
        ];
    }

    /**
     * Receipt groups for one step. Figures come from the preview of rows that
     * would be written. A total is not added across unlike units.
     *
     * @param  array{create: int, exists: int, creates?: list<array<string, mixed>>}  $preview
     * @param  array<string, mixed>  $payload
     * @return list<array{tone: string, name: string, rows: list<array{label: string, value: int}>}>
     */
    private function receipt(string $key, array $preview, array $payload, string $mode): array
    {
        $creates = $preview['creates'] ?? [];
        $createRows = match ($key) {
            'terms' => [['label' => 'Terms', 'value' => (int) $preview['create']]],
            'standards' => $this->classStreamFigures($creates, (int) $preview['create']),
            'subjects' => [
                ['label' => 'Subjects', 'value' => (int) $preview['create']],
                ['label' => 'Subject in a class', 'value' => $this->sumList($creates, 'classes')],
            ],
            'teachers' => [
                ['label' => 'Teachers', 'value' => (int) $preview['create']],
                ['label' => 'Subject assignments', 'value' => $this->assignmentCount($creates)],
            ],
            'students' => [
                ['label' => 'Learners', 'value' => (int) $preview['create']],
                ['label' => 'Parents', 'value' => $this->namedParents($creates)],
            ],
            default => [['label' => 'Rows', 'value' => (int) $preview['create']]],
        };

        if ($mode === 'result' && in_array($key, ['teachers', 'students'], true)) {
            $createRows[] = [
                'label' => 'Invites ready, not sent',
                'value' => (int) ($payload['results'][$key]['invites'] ?? 0),
            ];
        }

        $people = in_array($key, ['teachers', 'students'], true);
        $groups = [
            [
                'tone' => $people ? 'people' : 'structure',
                'name' => $mode === 'result' ? ($people ? 'People' : 'School structure') : 'Create',
                'rows' => $createRows,
            ],
            [
                'tone' => 'neutral',
                'name' => 'Already exists',
                'rows' => [['label' => 'Already stored', 'value' => (int) ($preview['exists'] ?? 0)]],
            ],
        ];

        if ($key === 'students') {
            $groups[] = [
                'tone' => 'flag',
                'name' => $mode === 'result' ? 'Left out' : 'Skipped',
                'rows' => [['label' => 'Flagged rows', 'value' => count($payload['skipped_flags'] ?? [])]],
            ];
        } else {
            $groups[] = [
                'tone' => 'flag',
                'name' => 'Conflict',
                'rows' => [['label' => 'Conflicts', 'value' => (int) ($preview['conflict'] ?? 0)]],
            ];
        }

        return $groups;
    }

    /**
     * @param  list<array<string, mixed>>  $creates
     * @return list<array{label: string, value: int}>
     */
    private function classStreamFigures(array $creates, int $fallback): array
    {
        $names = [];
        $streams = 0;
        foreach ($creates as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
            if (trim((string) ($row['stream'] ?? '')) !== '') {
                $streams++;
            }
        }

        if ($names === []) {
            return [['label' => 'Classes and streams', 'value' => $fallback]];
        }

        return [
            ['label' => 'Classes', 'value' => count($names)],
            ['label' => 'Streams', 'value' => $streams],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function sumList(array $rows, string $field): int
    {
        $total = 0;
        foreach ($rows as $row) {
            $value = $row[$field] ?? null;
            if (is_array($value)) {
                $total += count($value);
            }
        }

        return $total;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function assignmentCount(array $rows): int
    {
        $total = 0;
        foreach ($rows as $row) {
            $links = $row['resolved_links'] ?? $row['links'] ?? [];
            $total += is_array($links) ? count($links) : 0;
        }

        return $total;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function namedParents(array $rows): int
    {
        $total = 0;
        foreach ($rows as $row) {
            if (trim((string) ($row['parent_name'] ?? '')) !== '') {
                $total++;
            }
        }

        return $total;
    }

    private function proposalFor(string $key): string
    {
        return match ($key) {
            'terms' => 'Use the term names and dates as written, and nothing changes until you press the button.',
            'standards' => 'Use the stream names as written, in the class order on the sheet, and nothing changes until you press the button.',
            'subjects' => 'Attach each subject to the classes named on its row, and nothing changes until you press the button.',
            'teachers' => 'Add the teachers and the teaching assignments as written, and nothing changes until you press the button.',
            'students' => 'Add the learners and their parents, leave flagged rows out, and nothing changes until you press the button.',
            default => 'Use the rows as written on the sheet.',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $creates
     */
    private function approveLabel(string $key, int $create, array $creates = []): string
    {
        if ($create === 0) {
            return 'Approve, nothing new to create';
        }

        if ($key === 'standards') {
            return 'Create '.$this->classStreamPhrase($creates, $create);
        }

        $noun = match ($key) {
            'terms' => $create === 1 ? 'term' : 'terms',
            'subjects' => $create === 1 ? 'subject' : 'subjects',
            'teachers' => $create === 1 ? 'teacher' : 'teachers',
            'students' => $create === 1 ? 'learner' : 'learners',
            default => $create === 1 ? 'row' : 'rows',
        };

        return 'Create '.$create.' '.$noun;
    }

    /**
     * @param  list<array<string, mixed>>  $creates
     */
    private function doneLine(string $key, int $created, array $creates = []): string
    {
        if ($created === 0) {
            return 'Nothing new was created.';
        }

        if ($key === 'standards') {
            return 'Created '.$this->classStreamPhrase($creates, $created).'.';
        }

        return match ($key) {
            'terms' => 'Created '.$created.' '.($created === 1 ? 'term' : 'terms').'.',
            'subjects' => 'Created '.$created.' '.($created === 1 ? 'subject' : 'subjects').'.',
            'teachers' => 'Created '.$created.' '.($created === 1 ? 'teacher' : 'teachers').'.',
            'students' => 'Created '.$created.' '.($created === 1 ? 'learner' : 'learners').'.',
            default => 'Created '.$created.'.',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $creates
     */
    private function classStreamPhrase(array $creates, int $fallback): string
    {
        $figures = $this->classStreamFigures($creates, $fallback);
        if (count($figures) === 1) {
            $value = (int) $figures[0]['value'];

            return $value.' '.($value === 1 ? 'class' : 'classes and streams');
        }

        $classes = (int) $figures[0]['value'];
        $streams = (int) $figures[1]['value'];
        $classWord = $classes === 1 ? 'class' : 'classes';
        if ($streams === 0) {
            return $classes.' '.$classWord;
        }

        return $classes.' '.$classWord.' and '.$streams.' '.($streams === 1 ? 'stream' : 'streams');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{create: int, exists: int, conflict: int, conflicts: list<array{label: string, message: string}>}  $preview
     * @return array{prompt: string, blocks_write: bool, answers: list<array{id: string, label: string}>}|null
     */
    private function questionFor(array $payload, string $key, array $preview): ?array
    {
        if ($preview['conflict'] > 0) {
            $first = $preview['conflicts'][0] ?? ['label' => self::TITLES[$key], 'message' => 'This step conflicts with a row already stored.'];
            $prompt = $first['label'].'. '.$first['message'];
            if ($preview['conflict'] > 1) {
                $prompt .= ' '.$preview['conflict'].' conflicts. This is the first.';
            }

            return [
                'prompt' => $prompt,
                'blocks_write' => true,
                'answers' => [
                    ['id' => 'skip', 'label' => 'Skip this step'],
                ],
            ];
        }

        $streams = $this->ambiguousStreams($payload, $key);
        if ($streams !== null) {
            return $streams;
        }

        return $this->classWithoutTeacher($payload, $key);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{prompt: string, blocks_write: bool, answers: list<array{id: string, label: string}>}|null
     */
    private function ambiguousStreams(array $payload, string $key): ?array
    {
        if ($key !== 'standards') {
            return null;
        }

        $byClass = [];
        foreach ($payload['steps']['standards']['rows'] as $row) {
            $stream = trim((string) ($row['stream'] ?? ''));
            if ($stream !== '') {
                $byClass[$row['name']][$stream] = $stream;
            }
        }

        foreach ($byClass as $class => $streams) {
            $names = array_values($streams);
            foreach ($names as $short) {
                foreach ($names as $long) {
                    if ($short === $long || strlen($short) > 2) {
                        continue;
                    }
                    if (str_starts_with(mb_strtolower($long), mb_strtolower($short))) {
                        return [
                            'prompt' => $class.' lists '.$short.' and '.$long.'. Both names are on the sheet.',
                            'blocks_write' => false,
                            'answers' => [
                                ['id' => 'leave', 'label' => 'Keep both as written'],
                            ],
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{prompt: string, blocks_write: bool, answers: list<array{id: string, label: string}>}|null
     */
    private function classWithoutTeacher(array $payload, string $key): ?array
    {
        if ($key !== 'teachers') {
            return null;
        }

        $covered = [];
        foreach ($payload['steps']['teachers']['rows'] as $teacher) {
            foreach ($teacher['links'] as $link) {
                $covered[$this->key($link['class'].'|'.$link['stream'])] = true;
            }
        }

        foreach ($payload['steps']['standards']['rows'] as $class) {
            if (isset($covered[$this->key($class['name'].'|'.$class['stream'])])) {
                continue;
            }
            $label = trim($class['name'].' '.$class['stream']);

            return [
                'prompt' => $label.' has no teacher on the teaching assignments. Leave it without one for now?',
                'blocks_write' => false,
                'answers' => [
                    ['id' => 'leave', 'label' => 'Leave it without one for now'],
                ],
            ];
        }

        return null;
    }

    /**
     * @param  array{field?: string, tab?: string, row?: int|null}  $flag
     * @param  array<string, mixed>  $payload
     */
    /**
     * @param  array{field?: string, tab?: string, row?: int|null, message?: string}  $flag
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function presentFlag(array $flag, array $payload): array
    {
        $flag['fix'] = $this->plainFix($flag, $payload);
        $flag['value'] = $this->flagValue($flag);
        $flag['severity'] = $this->flagSeverity($flag);
        $flag['guidance'] = $flag['severity'] === 'blocking'
            ? 'Fix the workbook'
            : 'Safe to continue, fix it later in KlassApp';

        return $flag;
    }

    /**
     * @param  array{field?: string, message?: string}  $flag
     */
    private function flagValue(array $flag): string
    {
        $message = (string) ($flag['message'] ?? '');
        if (preg_match('/"([^"]*)"/', $message, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    /**
     * A missing required name, or a class name KlassApp does not know, blocks the row.
     * A parent phone or a date of birth can wait.
     *
     * @param  array{field?: string, message?: string}  $flag
     */
    private function flagSeverity(array $flag): string
    {
        $field = strtolower((string) ($flag['field'] ?? ''));
        $message = (string) ($flag['message'] ?? '');
        if (str_contains($message, 'is not a KlassApp class name')) {
            return 'blocking';
        }
        if (str_starts_with($message, 'Missing required') && (str_contains($field, 'name') || $field === 'term')) {
            return 'blocking';
        }
        if ($field === 'tab' && str_contains($message, 'was not found')) {
            return 'blocking';
        }

        return 'minor';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function emitRead(?callable $progress, string $key, array $payload): void
    {
        if ($progress === null) {
            return;
        }

        $rows = $payload['steps'][$key]['rows'] ?? [];
        $numbers = [];
        $tab = self::SOURCE_TABS[$key] ?? 'Checklist';
        $classNames = [];
        foreach ($rows as $row) {
            $source = is_array($row['source'] ?? null) ? $row['source'] : [];
            if (($source['tab'] ?? '') !== '') {
                $tab = (string) $source['tab'];
            }
            if (isset($source['row']) && is_numeric($source['row'])) {
                $numbers[] = (int) $source['row'];
            }
            if ($key === 'standards') {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name !== '') {
                    $classNames[$name] = true;
                }
            }
        }
        $count = $key === 'standards' && $classNames !== [] ? count($classNames) : count($rows);
        [$one, $many] = self::NOUNS[$key] ?? ['row', 'rows'];
        $span = '';
        if ($numbers !== []) {
            $min = min($numbers);
            $max = max($numbers);
            $span = $min === $max ? ', row '.$min : ', rows '.$min.' to '.$max;
        }
        $progress([
            'step' => $key,
            'line' => 'read-'.$key,
            'label' => 'Reading '.$tab.$span,
            'status' => 'done',
            'result' => $count.' '.($count === 1 ? $one : $many),
        ]);
    }

    private function plainFix(array $flag, array $payload): string
    {
        if ($this->parentPhoneOnly($flag, $payload)) {
            return 'Learner ready, parent skipped.';
        }

        $field = strtolower((string) ($flag['field'] ?? ''));
        if (str_contains($field, 'phone')) {
            return 'Correct the phone on that row, or skip the row.';
        }
        if (str_contains($field, 'class')) {
            return 'Use a class name KlassApp already knows, or skip the row.';
        }

        return 'Correct that cell, or skip the row.';
    }

    /**
     * A learner row whose only errors are parent phones stays out of Ready.
     * Skipping it leaves the learner and the parent uncreated, which is what Ready counts.
     *
     * @param  array{field?: string, tab?: string, row?: int|null}  $flag
     * @param  array<string, mixed>  $payload
     */
    private function parentPhoneOnly(array $flag, array $payload): bool
    {
        if (($flag['tab'] ?? '') !== 'Learners' || ($flag['row'] ?? null) === null) {
            return false;
        }

        $row = (int) $flag['row'];
        $matched = false;
        foreach ($payload['flags'] as $other) {
            if (($other['tab'] ?? '') !== 'Learners' || (int) ($other['row'] ?? 0) !== $row) {
                continue;
            }
            $matched = true;
            if (! str_contains(strtolower((string) ($other['field'] ?? '')), 'phone')) {
                return false;
            }
        }
        if (! $matched) {
            return false;
        }

        foreach ($payload['steps']['students']['rows'] as $student) {
            if ((int) ($student['source']['row'] ?? 0) === $row) {
                return true;
            }
        }

        return false;
    }

    /**
     * People figures after the flagged rows are left out, the same rows Done writes.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function leavingFlaggedRowsOut(array $payload): array
    {
        $flags = $payload['flags'] ?? [];
        foreach (self::WALK as $key) {
            $payload['steps'][$key]['rows'] = array_values(array_filter(
                $payload['steps'][$key]['rows'] ?? [],
                fn (array $row): bool => ! $this->rowIsFlagged($flags, (string) $row['source']['tab'], (int) $row['source']['row'])
            ));
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{label: string, value: string, detail: string}>
     */
    private function statTiles(array $payload): array
    {
        $students = $payload['steps']['students']['rows'];
        $classes = $payload['steps']['standards']['rows'];
        $classNames = [];
        $streams = 0;
        foreach ($classes as $row) {
            $classNames[$row['name']] = true;
            if (trim((string) ($row['stream'] ?? '')) !== '') {
                $streams++;
            }
        }
        $parents = 0;
        foreach ($students as $row) {
            if (trim((string) ($row['parent_name'] ?? '')) !== '') {
                $parents++;
            }
        }
        $terms = $payload['steps']['terms']['rows'];
        $termDetail = [];
        foreach ($terms as $term) {
            $termDetail[] = $term['name'].' '.$term['start'].' to '.$term['end'];
        }

        return [
            ['label' => 'Learners', 'value' => (string) count($students), 'detail' => ''],
            ['label' => 'Classes', 'value' => (string) count($classNames), 'detail' => ''],
            ['label' => 'Streams', 'value' => (string) $streams, 'detail' => ''],
            ['label' => 'Teachers', 'value' => (string) count($payload['steps']['teachers']['rows']), 'detail' => ''],
            ['label' => 'Parents', 'value' => (string) $parents, 'detail' => ''],
            ['label' => 'Subjects', 'value' => (string) count($payload['steps']['subjects']['rows']), 'detail' => ''],
            ['label' => 'Terms', 'value' => (string) count($terms), 'detail' => implode(' · ', $termDetail)],
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
            if ($index === 0) {
                continue;
            }
            $class = trim((string) ($row[0] ?? ''));
            if ($class === '' || strcasecmp($class, 'Total') === 0 || strcasecmp($class, 'Example') === 0 || str_starts_with(strtolower($class), 'example ')) {
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
     * Numbers and names the checklist chat is allowed to say.
     *
     * @return array<string, mixed>
     */
    public function conversationBrief(OnboardingPlan $plan): array
    {
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $key = $this->currentKey($payload);
        $step = null;
        $school = $plan->school;
        if ($key !== null && $school !== null) {
            $preview = $this->previewStep($school, $payload, $key);
            $step = [
                'title' => self::TITLES[$key] ?? $key,
                'proposal' => $this->proposalFor($key),
                'approve_label' => $this->approveLabel($key, $preview['create'], $preview['creates'] ?? []),
                'create' => $preview['create'],
                'exists' => $preview['exists'],
                'conflict' => $preview['conflict'],
            ];
        }

        $terms = [];
        foreach ($payload['steps']['terms']['rows'] ?? [] as $term) {
            $terms[] = trim((string) $term['name'].' '.(string) $term['start'].' to '.(string) $term['end']);
        }
        $classes = [];
        foreach ($payload['steps']['standards']['rows'] ?? [] as $row) {
            $classes[(string) $row['name']] = (string) $row['name'];
        }
        $subjects = [];
        foreach ($payload['steps']['subjects']['rows'] ?? [] as $row) {
            $subjects[] = (string) $row['name'];
        }
        $teachers = [];
        $assignments = [];
        foreach ($payload['steps']['teachers']['rows'] ?? [] as $row) {
            $teachers[] = (string) $row['name'];
            foreach ($row['links'] ?? [] as $link) {
                $assignments[] = [
                    'teacher' => (string) $row['name'],
                    'subject' => (string) ($link['subject'] ?? ''),
                    'class' => (string) ($link['class'] ?? ''),
                    'stream' => (string) ($link['stream'] ?? ''),
                ];
            }
        }
        $learners = [];
        foreach ($payload['steps']['students']['rows'] ?? [] as $row) {
            $learners[] = [
                'name' => (string) ($row['name'] ?? ''),
                'class' => (string) ($row['class'] ?? ''),
                'stream' => (string) ($row['stream'] ?? ''),
                'parent' => (string) ($row['parent_name'] ?? ''),
            ];
        }
        $classTeachers = [];
        foreach ($payload['class_teachers'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $classTeachers[] = [
                'class' => (string) ($row['class'] ?? ''),
                'stream' => (string) ($row['stream'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }
        $flags = [];
        foreach ($payload['flags'] ?? [] as $flag) {
            if (! is_array($flag)) {
                continue;
            }
            $flags[] = [
                'tab' => (string) ($flag['tab'] ?? ''),
                'row' => isset($flag['row']) ? (int) $flag['row'] : null,
                'message' => (string) ($flag['message'] ?? ''),
                'fix' => $this->plainFix($flag, $payload),
            ];
        }

        $status = (string) $plan->status;

        return [
            'school' => (string) ($payload['school']['name'] ?? ''),
            'status' => $status,
            'active' => $step !== null && in_array($status, ['previewed', 'applying'], true),
            'stats' => $this->statTiles($this->leavingFlaggedRowsOut($payload)),
            'flags' => $flags,
            'step' => $step,
            'terms' => $terms,
            'classes' => array_values($classes),
            'subjects' => $subjects,
            'teachers' => $teachers,
            'assignments' => $assignments,
            'learners' => $learners,
            'class_teachers' => $classTeachers,
            'class_teachers_listed' => (int) ($payload['class_teachers_listed'] ?? count($classTeachers)),
            'invites_ready' => (int) ($payload['invites_ready'] ?? 0),
        ];
    }

    /**
     * Facts a single chat call may see. Counts, classes, terms, flags, and the
     * waiting step. No learner names, parent names, or phone numbers.
     *
     * @return array<string, mixed>
     */
    public function narrationFacts(OnboardingPlan $plan): array
    {
        $brief = $this->conversationBrief($plan);
        $payload = is_array($plan->payload) ? $plan->payload : [];
        $classes = [];
        foreach ($payload['steps']['standards']['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $classes[$name]['name'] = $name;
            $stream = trim((string) ($row['stream'] ?? ''));
            if ($stream !== '') {
                $classes[$name]['streams'][$stream] = $stream;
            }
        }

        $classList = [];
        foreach ($classes as $class) {
            $classList[] = [
                'name' => $class['name'],
                'streams' => array_values($class['streams'] ?? []),
            ];
        }

        $terms = [];
        foreach ($payload['steps']['terms']['rows'] ?? [] as $term) {
            if (! is_array($term)) {
                continue;
            }
            $terms[] = [
                'name' => (string) ($term['name'] ?? ''),
                'start' => (string) ($term['start'] ?? ''),
                'end' => (string) ($term['end'] ?? ''),
            ];
        }

        $counts = [];
        foreach ($brief['stats'] as $tile) {
            $counts[mb_strtolower((string) $tile['label'])] = (string) $tile['value'];
        }

        $flags = [];
        foreach ($brief['flags'] as $flag) {
            $flags[] = [
                'tab' => $flag['tab'],
                'row' => $flag['row'],
                'fix' => $flag['fix'],
            ];
        }

        return [
            'school' => $brief['school'],
            'counts' => $counts,
            'classes' => $classList,
            'terms' => $terms,
            'flags' => $flags,
            'step' => $brief['step'],
        ];
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
