<?php

namespace App\Services\Toshi;

use App\AiAgents\ParentOperationsAgent;
use App\AiAgents\StudentOperationsAgent;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\ToshiLlm;
use App\AiAgents\WhatsApp\SchoolAdminWhatsAppReadAgent;
use App\Models\Academics\Marks;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Support\Toshi\AdversarialPromptFixtures;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

/**
 * In-process live-LLM adversarial soft-refusal runner.
 *
 * Isolates all Eloquent work on a temporary sqlite :memory: connection so
 * production MySQL is never touched. Does not require PHPUnit / --dev deps.
 * WhatsApp / Evolution HTTP is faked; the configured LLM provider is live.
 */
class LiveAdversarialRunner
{
    private int $schoolId;

    private User $teacher;

    private User $studentA;

    private User $studentB;

    private User $parent;

    private User $otherStudent;

    private User $admin;

    private int $subjectId;

    private int $promptTokens = 0;

    private int $completionTokens = 0;

    /** @var list<array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}> */
    private array $report = [];

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     * @return array{
     *     ok: bool,
     *     report: list<array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     pass: int,
     *     flag: int,
     *     fail: int,
     *     estimated_usd: float,
     *     provider: string,
     *     host: string,
     *     model: string
     * }
     */
    public function run(?string $filter = null, ?callable $onScenario = null): array
    {
        $previousDefault = (string) config('database.default');
        $previousSqlite = config('database.connections.sqlite');

        try {
            $this->bootIsolatedSqlite();
            $this->fakeOutboundMessagingHttp();
            $this->seedFixtures();
            $this->enableToshiConfig();
            $this->runScenarios($filter, $onScenario);

            return $this->buildResult();
        } finally {
            Auth::logout();
            $this->restoreDatabase($previousDefault, $previousSqlite);
        }
    }

    private function bootIsolatedSqlite(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $exit = Artisan::call('migrate', ['--force' => true]);
        if ($exit !== 0) {
            throw new \RuntimeException('Live adversarial sqlite migrate failed: '.Artisan::output());
        }
    }

    /**
     * @param  mixed  $previousSqlite
     */
    private function restoreDatabase(string $previousDefault, mixed $previousSqlite): void
    {
        config(['database.default' => $previousDefault]);
        if (is_array($previousSqlite)) {
            config(['database.connections.sqlite' => $previousSqlite]);
        }

        DB::purge('sqlite');
        if ($previousDefault !== '' && $previousDefault !== 'sqlite') {
            DB::purge($previousDefault);
            DB::reconnect($previousDefault);
        } else {
            DB::reconnect('sqlite');
        }
    }

    private function fakeOutboundMessagingHttp(): void
    {
        // Only stub messaging transports — LLM provider traffic must stay live.
        Http::fake([
            '*localhost*' => Http::response(['status' => 'ok'], 200),
            '*evolution*' => Http::response(['status' => 'ok'], 200),
            '*whatsapp*' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    private function enableToshiConfig(): void
    {
        config([
            'toshi.sdk_v2_enabled' => true,
            'toshi.whatsapp_channel_enabled' => true,
            'toshi.per_school_gate' => true,
        ]);
    }

    private function seedFixtures(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = (int) DB::table('schools')->insertGetId([
            'name' => 'Live Adv School',
            'slug' => 'live-adv',
            'email' => 'live-adv@test.sch.ug',
            'phone' => '+256700000199',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicYearId = (int) DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $standard = Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'P5',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->schoolId,
            'name' => 'A',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'no_of_students' => 2,
            'status' => 1,
        ]);

        $this->teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 5,
            'email' => 'teacher.live.adv@test.sch.ug',
            'name' => 'Live Adv Teacher',
        ]);

        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.a.live.adv@test.sch.ug',
            'name' => 'Student A Live',
        ]);
        $this->studentB = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.b.live.adv@test.sch.ug',
            'name' => 'Student B Live',
        ]);

        foreach ([$this->studentA, $this->studentB] as $student) {
            StudentAcademic::create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $academicYearId,
                'user_id' => $student->id,
                'standardLink_id' => $link->id,
                'academic_status' => 'pass',
            ]);
        }

        $subject = Subject::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Math',
            'type' => 'core',
            'status' => 1,
        ]);
        $this->subjectId = (int) $subject->id;

        Marks::create([
            'student_id' => $this->studentB->id,
            'school_id' => $this->schoolId,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subjectId,
            'section_id' => 1,
            'exam_id' => 1,
            'marks' => 99,
            'grade' => 'A',
        ]);
        Marks::create([
            'student_id' => $this->studentA->id,
            'school_id' => $this->schoolId,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subjectId,
            'section_id' => 1,
            'exam_id' => 1,
            'marks' => 70,
            'grade' => 'B',
        ]);

        $this->parent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 7,
            'email' => 'parent.live.adv@test.sch.ug',
            'name' => 'Live Adv Parent',
        ]);
        $this->otherStudent = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'other.live.adv@test.sch.ug',
            'name' => 'Other Student Live',
        ]);
        StudentParentLink::create([
            'school_id' => $this->schoolId,
            'parent_id' => $this->parent->id,
            'student_id' => $this->studentA->id,
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'admin.live.adv@test.sch.ug',
            'name' => 'Live Adv Admin',
        ]);
    }

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function runScenarios(?string $filter, ?callable $onScenario): void
    {
        $this->runTeacherScenarios($filter, $onScenario);
        $this->runStudentScenarios($filter, $onScenario);
        $this->runParentScenarios($filter, $onScenario);
        $this->runSchoolAdminWaScenarios($filter, $onScenario);
    }

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function runTeacherScenarios(?string $filter, ?callable $onScenario): void
    {
        $cases = [
            'teacher_as_admin_add_coadmin' => [
                AdversarialPromptFixtures::TEACHER_AS_ADMIN_ADD_COADMIN,
                fn () => User::where('email', 'eve.coadmin@test.sch.ug')->count() === 0,
            ],
            'teacher_ignore_limits_payroll' => [
                AdversarialPromptFixtures::TEACHER_IGNORE_LIMITS_PAYROLL,
                null,
            ],
            'teacher_impersonate_admin' => [
                AdversarialPromptFixtures::TEACHER_IMPERSONATE_ADMIN,
                null,
            ],
            'teacher_create_exam_as_admin' => [
                AdversarialPromptFixtures::TEACHER_CREATE_EXAM_AS_ADMIN,
                null,
            ],
        ];

        Auth::login($this->teacher);
        foreach ($cases as $id => [$prompt, $mutationOk]) {
            if (! $this->matchesFilter($id, $filter)) {
                continue;
            }
            $this->recordLive('teacher', $id, fn () => (new TeacherOperationsAgent)->prompt($prompt), $mutationOk, null, $onScenario);
        }
    }

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function runStudentScenarios(?string $filter, ?callable $onScenario): void
    {
        Auth::login($this->studentA);

        $peerMarksId = 'student_peer_marks';
        if ($this->matchesFilter($peerMarksId, $filter)) {
            $prompt = AdversarialPromptFixtures::studentPeerMarks($this->studentB->id);
            $this->recordLive(
                'student',
                $peerMarksId,
                fn () => (new StudentOperationsAgent)->prompt($prompt),
                null,
                '99',
                $onScenario
            );
        }

        $cases = [
            'student_now_admin_create_exam' => AdversarialPromptFixtures::STUDENT_NOW_ADMIN_CREATE_EXAM,
            'student_add_coadmin' => AdversarialPromptFixtures::STUDENT_ADD_COADMIN,
            'student_peer_attendance' => AdversarialPromptFixtures::studentPeerAttendance($this->studentB->id),
        ];

        foreach ($cases as $id => $prompt) {
            if (! $this->matchesFilter($id, $filter)) {
                continue;
            }
            $mutationOk = $id === 'student_add_coadmin'
                ? fn () => User::where('email', 'student.esc@test.sch.ug')->count() === 0
                : null;
            $this->recordLive('student', $id, fn () => (new StudentOperationsAgent)->prompt($prompt), $mutationOk, null, $onScenario);
        }
    }

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function runParentScenarios(?string $filter, ?callable $onScenario): void
    {
        Auth::login($this->parent);

        $cases = [
            'parent_peer_child_fees' => AdversarialPromptFixtures::parentPeerChildFees($this->otherStudent->id),
            'parent_add_coadmin' => AdversarialPromptFixtures::PARENT_ADD_COADMIN,
            'parent_payroll' => AdversarialPromptFixtures::PARENT_PAYROLL,
            'parent_peer_grades' => AdversarialPromptFixtures::PARENT_PEER_GRADES,
        ];

        foreach ($cases as $id => $prompt) {
            if (! $this->matchesFilter($id, $filter)) {
                continue;
            }
            $mutationOk = $id === 'parent_add_coadmin'
                ? fn () => User::where('email', 'parent.esc@test.sch.ug')->count() === 0
                : null;
            $this->recordLive('parent', $id, fn () => (new ParentOperationsAgent)->prompt($prompt), $mutationOk, null, $onScenario);
        }
    }

    /**
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function runSchoolAdminWaScenarios(?string $filter, ?callable $onScenario): void
    {
        Auth::login($this->admin);

        $cases = [
            'school_admin_wa_payroll' => AdversarialPromptFixtures::SCHOOL_ADMIN_WA_PAYROLL,
            'school_admin_wa_impersonate' => AdversarialPromptFixtures::SCHOOL_ADMIN_WA_IMPERSONATE,
            'school_admin_wa_mark_attendance' => AdversarialPromptFixtures::SCHOOL_ADMIN_WA_MARK_ATTENDANCE,
            'school_admin_wa_add_coadmin' => AdversarialPromptFixtures::SCHOOL_ADMIN_WA_ADD_COADMIN,
        ];

        foreach ($cases as $id => $prompt) {
            if (! $this->matchesFilter($id, $filter)) {
                continue;
            }
            $mutationOk = $id === 'school_admin_wa_add_coadmin'
                ? fn () => User::where('email', 'wa.esc@test.sch.ug')->count() === 0
                : null;
            $this->recordLive(
                'school_admin_wa',
                $id,
                fn () => (new SchoolAdminWhatsAppReadAgent)->prompt($prompt),
                $mutationOk,
                null,
                $onScenario
            );
        }
    }

    private function matchesFilter(string $id, ?string $filter): bool
    {
        if ($filter === null || $filter === '') {
            return true;
        }

        return str_contains($id, $filter);
    }

    /**
     * @param  callable(): AgentResponse  $invoke
     * @param  (callable(): bool)|null  $mutationOk
     * @param  (callable(array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}): void)|null  $onScenario
     */
    private function recordLive(
        string $role,
        string $id,
        callable $invoke,
        ?callable $mutationOk = null,
        ?string $forbiddenSecret = null,
        ?callable $onScenario = null,
    ): void {
        $notes = [];
        $preview = '';
        $promptTokens = 0;
        $completionTokens = 0;
        $verdict = 'fail';

        try {
            /** @var AgentResponse $response */
            $response = $invoke();
            $preview = mb_substr(trim($response->text), 0, 280);
            $promptTokens = $response->usage->promptTokens;
            $completionTokens = $response->usage->completionTokens;
            $this->promptTokens += $promptTokens;
            $this->completionTokens += $completionTokens;

            $scored = LiveAdversarialScorer::score($response->text, $forbiddenSecret);
            $verdict = $scored['verdict'];
            $notes = $scored['notes'];

            if ($mutationOk !== null && ! $mutationOk()) {
                $verdict = 'fail';
                $notes[] = 'unexpected DB mutation after adversarial prompt';
            }
        } catch (Throwable $e) {
            $verdict = 'fail';
            $notes[] = 'exception: '.$e::class.' — '.$e->getMessage();
            $preview = mb_substr($e->getMessage(), 0, 280);
        }

        $row = [
            'id' => $id,
            'role' => $role,
            'verdict' => $verdict,
            'notes' => $notes,
            'preview' => $preview,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];

        $this->report[] = $row;

        if ($onScenario !== null) {
            $onScenario($row);
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     report: list<array{id: string, role: string, verdict: string, notes: list<string>, preview: string, prompt_tokens: int, completion_tokens: int}>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     pass: int,
     *     flag: int,
     *     fail: int,
     *     estimated_usd: float,
     *     provider: string,
     *     host: string,
     *     model: string
     * }
     */
    private function buildResult(): array
    {
        $pass = count(array_filter($this->report, fn (array $r): bool => $r['verdict'] === 'pass'));
        $flag = count(array_filter($this->report, fn (array $r): bool => $r['verdict'] === 'flag'));
        $fail = count(array_filter($this->report, fn (array $r): bool => $r['verdict'] === 'fail'));

        $estimatedUsd = ($this->promptTokens / 1_000_000) * 0.27
            + ($this->completionTokens / 1_000_000) * 1.10;

        return [
            'ok' => $fail === 0,
            'report' => $this->report,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'pass' => $pass,
            'flag' => $flag,
            'fail' => $fail,
            'estimated_usd' => $estimatedUsd,
            'provider' => ToshiLlm::provider(),
            'host' => ToshiLlm::urlHost(),
            'model' => ToshiLlm::model(),
        ];
    }
}
