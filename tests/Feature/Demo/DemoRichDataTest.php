<?php

namespace Tests\Feature\Demo;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\TimetableSlot;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\NoticeBoard;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Support\DemoSeedManifest;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Rich demo data for the two walkthrough schools (Task 2).
 *
 * FK pragma is ON so a wrong delete order in demo:refresh fails here
 * instead of on staging (MySQL enforces FKs; sqlite does not by default).
 */
class DemoRichDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \DB::statement('PRAGMA foreign_keys = ON');
    }

    private function seedJunior(): School
    {
        $this->seed(DemoJuniorSchoolSeeder::class);

        return School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();
    }

    private function seedSenior(): School
    {
        $this->seed(DemoSeniorSchoolSeeder::class);

        return School::where('email', 'demo-senior@klassapp.xyz')->firstOrFail();
    }

    /**
     * @return array<string, int>
     */
    private function richCounts(School $school): array
    {
        return [
            'parents' => User::where('school_id', $school->id)->where('usergroup_id', 7)->count(),
            'parent_links' => StudentParentLink::where('school_id', $school->id)->count(),
            'slots' => DB::table('timetable_slots')->where('school_id', $school->id)->count(),
            'notices' => NoticeBoard::where('school_id', $school->id)->count(),
            'events' => DB::table('events')->where('school_id', $school->id)->count(),
            'admissions' => Admission::where('school_id', $school->id)->count(),
            'attendance' => Attendance::where('school_id', $school->id)->count(),
            'exams' => Exam::where('school_id', $school->id)->count(),
            'marks' => DB::table('marks')->where('school_id', $school->id)->count(),
            'submissions' => DB::table('exam_marks_submissions')->where('school_id', $school->id)->count(),
        ];
    }

    // ────────────────────────────────────────────── rich data, both schools

    public function test_junior_school_gets_rich_demo_data(): void
    {
        $school = $this->seedJunior();

        $this->assertSame(1, User::where('school_id', $school->id)->where('usergroup_id', 7)->where('email', 'parent1@junior.demo.klassapp.test')->count(), 'parent1 exists');
        $this->assertGreaterThanOrEqual(10, User::where('school_id', $school->id)->where('usergroup_id', 7)->count(), 'parents');
        $this->assertGreaterThanOrEqual(10, StudentParentLink::where('school_id', $school->id)->count(), 'parent links');
        $this->assertGreaterThanOrEqual(100, DB::table('timetable_slots')->where('school_id', $school->id)->count(), 'timetable slots');
        $this->assertGreaterThanOrEqual(3, NoticeBoard::where('school_id', $school->id)->count(), 'notices');
        $this->assertDatabaseHas('events', ['school_id' => $school->id, 'title' => 'Visitation Day']);
        $this->assertDatabaseHas('events', ['school_id' => $school->id, 'title' => 'Inter-house Sports Gala']);
        $this->assertSame(2, Admission::where('school_id', $school->id)->where('application_status', 'Pending')->count(), 'pending admissions');

        // Ugandan admission fields are stored in the controller's real format.
        $admission = Admission::where('school_id', $school->id)->firstOrFail();
        $this->assertContains($admission->entry_term, ['1', '2', '3'], 'entry_term uses 1|2|3, not "Term I"');
        $this->assertSame(0, Admission::where('school_id', $school->id)->where('entry_term', 'Term I')->count());

        // Parents carry mobile_no (the real column), not phone/whatsapp_number.
        $parentCols = \Schema::getColumnListing('users');
        $this->assertContains('mobile_no', $parentCols);
        $this->assertNotContains('phone', $parentCols);
        $this->assertNotContains('whatsapp_number', $parentCols);
        $parent = User::where('school_id', $school->id)->where('email', 'parent1@junior.demo.klassapp.test')->firstOrFail();
        $this->assertNotNull($parent->mobile_no, 'parent has a fictional mobile_no');
        $this->assertStringStartsWith('070', (string) $parent->mobile_no);

        // Previous term fully locked and approved; current term includes an open exam.
        $this->assertGreaterThan(0, DB::table('exam_marks_submissions')->where('school_id', $school->id)->whereNotNull('locked_at')->count(), 'locked submissions');
        $this->assertGreaterThan(0, DB::table('exam_marks_submissions')->where('school_id', $school->id)->where('approval_status', 'approved')->count(), 'approved submissions');

        // Timetable slots produce recurring calendar events.
        $this->assertGreaterThan(0, DB::table('events')->where('school_id', $school->id)->whereNotNull('timetable_slot_id')->count(), 'timetable calendar events');

        $manifest = DemoSeedManifest::read($school->id);
        $this->assertNotNull($manifest, 'manifest written');
        $this->assertSame('Demo Junior School', $manifest['school_name'] ?? null);

        $walk = $manifest['walkthrough'] ?? [];
        $openExamId = $walk['open_exam_id'] ?? null;
        $this->assertNotNull($openExamId, 'open exam recorded');
        $openExam = Exam::find($openExamId);
        $this->assertNotNull($openExam, 'open exam exists');
        $this->assertSame(0, DB::table('marks')->where('exam_id', $openExamId)->count(), 'open exam unmarked');
        $this->assertSame(0, DB::table('exam_marks_submissions')->where('exam_id', $openExamId)->count(), 'open exam has no submission');

        // The open exam must be teacher1's to enter (own subject, own class).
        $teacher1 = User::where('school_id', $school->id)->where('email', 'teacher1@junior.demo.klassapp.test')->firstOrFail();
        $this->assertSame($teacher1->id, (int) $openExam->teacher_id, 'open exam belongs to teacher1');
        $this->assertNotNull(StandardLink::find($openExam->section_id === null ? null : $openExam->section_id) ?: DB::table('standards_link')->where('section_id', $openExam->section_id)->orderBy('id')->value('id'), 'open exam class exists');

        // Walkthrough classes have no attendance for today; other classes do.
        $skipIds = $walk['skip_link_ids'] ?? [];
        $this->assertNotEmpty($skipIds, 'skip links recorded');

        foreach ($skipIds as $linkId) {
            $this->assertSame(0, Attendance::where('school_id', $school->id)
                ->where('standardLink_id', $linkId)
                ->where('date', now()->toDateString())
                ->count(), 'walkthrough class untaken today');
        }

        $otherLink = DB::table('standards_link')->where('school_id', $school->id)
            ->whereNotIn('id', $skipIds)
            ->orderBy('id')->value('id');

        $this->assertNotNull($otherLink, 'there is a non-walkthrough class');
        $this->assertGreaterThan(0, Attendance::where('school_id', $school->id)
            ->where('standardLink_id', $otherLink)
            ->where('date', now()->toDateString())
            ->count(), 'other classes have attendance today');
    }

    public function test_senior_school_gets_rich_demo_data(): void
    {
        $school = $this->seedSenior();

        $this->assertGreaterThanOrEqual(10, User::where('school_id', $school->id)->where('usergroup_id', 7)->count(), 'parents');
        $this->assertGreaterThanOrEqual(10, StudentParentLink::where('school_id', $school->id)->count(), 'parent links');
        $this->assertGreaterThanOrEqual(100, DB::table('timetable_slots')->where('school_id', $school->id)->count(), 'timetable slots');
        $this->assertGreaterThanOrEqual(3, NoticeBoard::where('school_id', $school->id)->count(), 'notices');
        $this->assertSame(2, Admission::where('school_id', $school->id)->where('application_status', 'Pending')->count(), 'pending admissions');

        $manifest = DemoSeedManifest::read($school->id);
        $this->assertNotNull($manifest, 'manifest written');
        $this->assertSame('Demo Senior School', $manifest['school_name'] ?? null);

        $openExamId = $manifest['walkthrough']['open_exam_id'] ?? null;
        $this->assertNotNull($openExamId, 'open exam recorded');
        $this->assertNotNull(Exam::find($openExamId), 'open exam exists');
        $this->assertSame(0, DB::table('marks')->where('exam_id', $openExamId)->count(), 'open exam unmarked');
        $this->assertSame(0, DB::table('exam_marks_submissions')->where('exam_id', $openExamId)->count(), 'open exam has no submission');

        // The open exam belongs to teacher1 (the walkthrough teacher).
        $teacher1 = User::where('school_id', $school->id)->where('email', 'teacher1@senior.demo.klassapp.test')->firstOrFail();
        $this->assertSame($teacher1->id, (int) Exam::find($openExamId)->teacher_id, 'open exam belongs to teacher1');

        // A-level forms are covered by the exam rounds too.
        $this->assertGreaterThan(0, Exam::where('school_id', $school->id)
            ->whereIn('section_id', \App\Models\Section::where('school_id', $school->id)->whereIn('name', ['S.5', 'S.6'])->pluck('id'))
            ->count(), 'S.5/S.6 covered by exam rounds');
    }

    // ────────────────────────────────────────────────────────── idempotency

    public function test_rich_demo_data_is_idempotent(): void
    {
        $school = $this->seedJunior();
        $before = $this->richCounts($school);
        $openBefore = DemoSeedManifest::read($school->id)['walkthrough']['open_exam_id'] ?? null;

        $this->seed(DemoJuniorSchoolSeeder::class);
        $after = $this->richCounts($school);
        $openAfter = DemoSeedManifest::read($school->id)['walkthrough']['open_exam_id'] ?? null;

        $this->assertSame($before, $after, 'junior rich counts stable across re-runs');
        $this->assertSame($openBefore, $openAfter, 'open exam id stable');
    }

    public function test_senior_rich_demo_data_is_idempotent(): void
    {
        $school = $this->seedSenior();
        $before = $this->richCounts($school);

        $this->seed(DemoSeniorSchoolSeeder::class);
        $after = $this->richCounts($school);

        $this->assertSame($before, $after, 'senior rich counts stable across re-runs');
    }

    // ────────────────────────────────────────────── manifest lives in the DB

    public function test_manifest_is_stored_in_school_details_table(): void
    {
        $school = $this->seedJunior();

        $row = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'demo_manifest')->first();
        $this->assertNotNull($row, 'demo_manifest row exists in school_details');

        $payload = json_decode((string) $row->meta_value, true);
        $this->assertIsArray($payload, 'manifest is JSON');
        $this->assertSame('Demo Junior School', $payload['school_name'] ?? null);
        $this->assertArrayHasKey('user_emails', $payload);
        $this->assertArrayHasKey('walkthrough', $payload);
        // DemoSeedManifest::read must resolve from the DB row.
        $this->assertSame($payload['school_name'] ?? null, DemoSeedManifest::read($school->id)['school_name'] ?? null, 'DemoSeedManifest::read returns the DB manifest');

        // No credentials in the manifest.
        $this->assertStringNotContainsString('password', strtolower($row->meta_value), 'manifest never contains passwords');

        // And no manifest files on disk.
        $this->assertFileDoesNotExist(storage_path('app/demo/' . $school->id . '.json'), 'manifest is not written to storage');
    }

    // ────────────────────────────────────────────────── per-account passwords

    public function test_each_new_account_gets_a_unique_random_password_and_no_env_password_is_read(): void
    {
        $school = $this->seedJunior();

        $users = User::where('school_id', $school->id)->orderBy('id')->get();
        $this->assertGreaterThan(20, $users->count(), 'enough users to check uniqueness');

        $hashes = $users->pluck('password')->all();
        $this->assertCount(count(array_unique($hashes)), $hashes, 'every account has a distinct password hash');

        foreach ($users as $user) {
            $this->assertNotSame('', trim((string) $user->password));
            $this->assertStringStartsWith('$2y$', (string) $user->password, 'bcrypt hash present');
        }
    }

    public function test_existing_account_passwords_survive_a_seeder_rerun(): void
    {
        $school = $this->seedJunior();
        $admin = User::where('school_id', $school->id)->where('email', 'admin@junior.demo.klassapp.test')->firstOrFail();
        $hashBefore = $admin->password;

        // This must NOT be overwritten by a rerun (password-on-create policy).
        $this->seed(DemoJuniorSchoolSeeder::class);

        $this->assertSame($hashBefore, $admin->refresh()->password, 'rerun must not overwrite an existing account password');
    }

    public function test_seeders_do_not_read_a_shared_env_password(): void
    {
        $base = realpath(__DIR__ . '/../../../database/seeders');
        $this->assertNotFalse($base);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        $offenders = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            foreach (['STAGING_DEMO_PASSWORD', 'DEMO_SEED_PASSWORD'] as $needle) {
                if (str_contains($source, $needle)) {
                    $offenders[] = $file->getPathname() . ' → ' . $needle;
                }
            }
        }

        $this->assertSame([], $offenders, 'demo seeders must never read a shared env password: ' . implode('; ', $offenders));
    }

    // ─────────────────────────────────────────────────────── demo:refresh

    public function test_demo_refresh_restores_baseline_and_keeps_accounts(): void
    {
        $school = $this->seedJunior();
        $manifest = DemoSeedManifest::read($school->id);
        $walk = $manifest['walkthrough'];
        $skipId = $walk['skip_link_ids'][0];
        $openExamId = $walk['open_exam_id'];

        $admin = User::where('school_id', $school->id)->where('email', 'admin@junior.demo.klassapp.test')->firstOrFail();
        $adminHash = $admin->password;
        $teacher1 = User::where('school_id', $school->id)->where('email', 'teacher1@junior.demo.klassapp.test')->firstOrFail();
        $teacher1Hash = $teacher1->password;

        // Simulate a testing session on the demo school.
        $fake = new User;
        $fake->forceFill([
            'email' => 'test-added-' . uniqid() . '@example.com',
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Test Added Student',
            'password' => Hash::make('irrelevant'),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        StudentAcademic::create([
            'school_id' => $school->id,
            'user_id' => $fake->id,
            'standardLink_id' => $skipId,
            'academic_year_id' => \App\Models\AcademicYear::where('school_id', $school->id)->orderBy('id')->value('id'),
        ]);

        FeePayment::create([
            'school_id' => $school->id,
            'fee_category_id' => null,
            'user_id' => $fake->id,
            'amount' => 1000,
            'paid_on' => now()->toDateString(),
            'recorded_by' => $admin->id,
            'status' => 'paid',
        ]);

        DB::table('marks')->insert([
            'student_id' => $fake->id,
            'teacher_id' => $admin->id,
            'school_id' => $school->id,
            'subject_id' => DB::table('subjects')->where('school_id', $school->id)->value('id'),
            'exam_id' => $openExamId,
            'section_id' => (int) \App\Models\Academics\Exam::find($openExamId)->section_id,
            'remark_id' => null,
            'marks' => 90,
            'grade' => 'D1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Attendance::create([
            'school_id' => $school->id,
            'academic_year_id' => \App\Models\AcademicYear::where('school_id', $school->id)->orderBy('id')->value('id'),
            'standardLink_id' => $skipId,
            'user_id' => $fake->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => true,
            'reason_id' => null,
            'remarks' => '',
            'recorded_by' => $admin->id,
        ]);

        $this->artisan('demo:refresh', ['school' => $school->id])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $fake->id]);
        $this->assertDatabaseMissing('student_academics', ['user_id' => $fake->id]);
        $this->assertDatabaseMissing('fee_payments', ['user_id' => $fake->id]);
        $this->assertSame(0, Attendance::where('standardLink_id', $skipId)->where('date', now()->toDateString())->count(), 'walkthrough class untaken again');
        $this->assertSame(0, DB::table('marks')->where('exam_id', $openExamId)->count(), 'open exam unmarked again');
        $this->assertSame(0, DB::table('exam_marks_submissions')->where('exam_id', $openExamId)->count(), 'open exam submission gone again');

        // Accounts and passwords that pre-dated the refresh are untouched.
        $this->assertSame($adminHash, $admin->refresh()->password, 'admin password untouched');
        $this->assertSame($teacher1Hash, User::find($teacher1->id)->password, 'teacher1 password untouched');
        $this->assertGreaterThanOrEqual(10, User::where('school_id', $school->id)->where('usergroup_id', 7)->count(), 'parents still there');
        $this->assertNotNull(User::find($admin->id), 'admin survives');
        $this->assertNotNull(User::find($teacher1->id), 'teacher1 survives');

        // Rich data still present after the refresh's top-up.
        $this->assertGreaterThanOrEqual(3, NoticeBoard::where('school_id', $school->id)->count(), 'notices still there');
        $this->assertGreaterThan(0, DB::table('timetable_slots')->where('school_id', $school->id)->count(), 'timetable still there');
    }

    public function test_demo_refresh_dry_run_changes_nothing(): void
    {
        $school = $this->seedJunior();

        $fake = new User;
        $fake->forceFill([
            'email' => 'dry-run-' . uniqid() . '@example.com',
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Dry Run Student',
            'password' => Hash::make('irrelevant'),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        $countsBefore = $this->richCounts($school) + ['users' => User::where('school_id', $school->id)->count()];

        $this->artisan('demo:refresh', ['school' => $school->id, '--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $fake->id]);
        $countsAfter = $this->richCounts($school) + ['users' => User::where('school_id', $school->id)->count()];
        $this->assertSame($countsBefore, $countsAfter, 'dry run changed no counts');
    }

    public function test_demo_refresh_refuses_a_non_demo_school(): void
    {
        $school = School::firstOrCreate(
            ['email' => 'not-demo@example.com'],
            ['name' => 'Regular School', 'slug' => 'regular-school', 'status' => 1]
        );

        $user = new User;
        $user->forceFill([
            'email' => 'keep-me@example.com',
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Keep Me',
            'password' => Hash::make('irrelevant'),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        $this->artisan('demo:refresh', ['school' => $school->id])->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ─────────────────────────────────────────────────── demo comms guard

    public function test_seeded_demo_schools_stay_blocked_from_outbound_comms(): void
    {
        $this->seedJunior();
        $this->seedSenior();

        foreach (School::whereIn('email', ['demo-junior@klassapp.xyz', 'demo-senior@klassapp.xyz'])->get() as $school) {
            $this->assertSame(1, (int) $school->is_demo, 'is_demo set');

            foreach (User::where('school_id', $school->id)->get() as $user) {
            $this->assertTrue(
                \App\Services\DemoSchoolCommsGuard::blocksSchool($school->id),
                "outbound comms blocked for {$school->name}"
            );
            $this->assertTrue(
                \App\Services\DemoSchoolCommsGuard::blocksEmail($user->email),
                "email blocked for {$school->name} / {$user->email}"
            );
            }
        }
    }

    public function test_exam_type_seed_does_not_duplicate_contributes_flag(): void
    {
        $this->seedJunior();

        $this->assertSame(1, ExamType::where('code', 'EOT')->count(), 'single EOT');
        $this->assertSame(1, (int) ExamType::where('code', 'EOT')->value('contributes_to_report_total'), 'EOT contributes to report total');
        $this->assertSame(0, (int) ExamType::where('code', 'BOT')->value('contributes_to_report_total'), 'BOT does not contribute');
    }
}
