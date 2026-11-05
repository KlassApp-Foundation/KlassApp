<?php

namespace Tests\Feature\Academics;

use App\Http\Middleware\MustBePrivilege;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\DashboardV2DataService;
use App\Services\MarksImport\MarksImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base-class views count the students who live on the class's streams.
 */
class BaseClassViewFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $this->withoutMiddleware(MustBePrivilege::class);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['dashboard.v2_enabled' => true]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_gender_chart_counts_every_stream_in_the_base_class(): void
    {
        [$school, $admin, $year, $standard] = $this->school();
        $base = $this->section($school, 'Primary Seven');
        $east = $this->section($school, 'Primary Seven E');
        $west = $this->section($school, 'Primary Seven W');
        $eastLink = $this->link($school, $year, $standard, $east);
        $westLink = $this->link($school, $year, $standard, $west);
        $this->link($school, $year, $standard, $base);
        $this->student($school, $year, $eastLink, 'Amina', 'female');
        $this->student($school, $year, $westLink, 'Brian', 'male');

        $other = School::create([
            'name' => 'Other', 'slug' => 'other-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999), 'status' => 1,
        ]);
        $this->student($other, $year, null, 'Outsider', 'female');

        $data = app(DashboardV2DataService::class)->build($school, $admin);
        $row = collect($data['charts']['gender_classes'])->firstWhere('label', 'Primary Seven');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['girls']);
        $this->assertSame(1, $row['boys']);
        $this->assertSame(1, $data['charts']['gender']['girls']);
        $this->assertSame(1, $data['charts']['gender']['boys']);
    }

    public function test_base_class_marks_template_lists_every_stream_and_a_filled_sheet_saves(): void
    {
        [$school, $admin, $year, $standard] = $this->school();
        $base = $this->section($school, 'Primary Seven');
        $east = $this->section($school, 'Primary Seven E');
        $west = $this->section($school, 'Primary Seven W');
        $baseLink = $this->link($school, $year, $standard, $base);
        $eastStudent = $this->student($school, $year, $this->link($school, $year, $standard, $east), 'Amina', 'female', 'KLS0000101');
        $westStudent = $this->student($school, $year, $this->link($school, $year, $standard, $west), 'Brian', 'male', 'KLS0000102');
        $exam = $this->markedExam($school, $admin, $year, $standard, $base, $baseLink, withMark: false);
        SchoolGradingSystem::create([
            'school_id' => $school->id, 'standard_id' => $standard->id,
            'grade' => 'A', 'points' => 1, 'min_score' => 0, 'max_score' => 100, 'remark' => 'All',
        ]);

        $service = app(MarksImportService::class);
        $rows = $service->templateRows($exam, $admin);
        $numbers = array_column($rows, 0);

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(['KLS0000101', 'KLS0000102'], $numbers);

        $plan = $service->preview($exam, $admin, [
            $service->templateHeadings(),
            ['KLS0000101', 'Amina', '80'],
            ['KLS0000102', 'Brian', '70'],
        ]);
        $this->assertFalse($plan->isBlocked());
        $result = $service->commit($plan, $admin);

        $this->assertSame(2, $result->saved);
        $this->assertSame(80.0, (float) Marks::query()->where('student_id', $eastStudent->id)->value('marks'));
        $this->assertSame(70.0, (float) Marks::query()->where('student_id', $westStudent->id)->value('marks'));
    }

    public function test_legacy_attendance_lists_every_stream_for_a_base_class(): void
    {
        [$school, $admin, $year, $standard] = $this->school();
        $base = $this->section($school, 'Primary Seven');
        $east = $this->section($school, 'Primary Seven E');
        $west = $this->section($school, 'Primary Seven W');
        $baseLink = $this->link($school, $year, $standard, $base);
        $eastLink = $this->link($school, $year, $standard, $east);
        $westLink = $this->link($school, $year, $standard, $west);
        $eastStudent = $this->student($school, $year, $eastLink, 'Amina', 'female');
        $westStudent = $this->student($school, $year, $westLink, 'Brian', 'male');
        foreach ([[$eastStudent, $eastLink], [$westStudent, $westLink]] as [$student, $link]) {
            Attendance::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standardLink_id' => $link->id,
                'user_id' => $student->id,
                'date' => now()->toDateString(),
                'session' => 'forenoon',
                'status' => 1,
                'recorded_by' => $admin->id,
            ]);
        }

        $list = $this->actingAs($admin)->getJson('/admin/attendance/list')->assertOk()->json('studentlist');
        $ids = collect($list[(string) $baseLink->id] ?? $list[$baseLink->id] ?? [])->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $this->assertEqualsCanonicalizing([$eastStudent->id, $westStudent->id], $ids);

        $page = $this->actingAs($admin)->get('/admin/attendance?section='.$base->id)->assertOk();
        $page->assertSee('AMINA');
        $page->assertSee('BRIAN');
    }

    public function test_performance_and_class_marks_list_every_class_in_level_order(): void
    {
        [$school, $admin, $year, $standard] = $this->school();
        $senior = $this->section($school, 'S.1');
        $second = $this->section($school, 'P.2');
        $first = $this->section($school, 'Primary One');
        $this->link($school, $year, $standard, $senior);
        $this->link($school, $year, $standard, $second);
        $firstLink = $this->link($school, $year, $standard, $first);
        $this->markedExam($school, $admin, $year, $standard, $first, $firstLink);

        $charts = app(DashboardV2DataService::class)->build($school, $admin)['charts']['per_class'];
        $this->assertSame(['Primary One', 'P.2', 'S.1'], array_column($charts, 'label'));
        $this->assertNotNull($charts[0]['value']);
        $this->assertNull($charts[1]['value']);
        $this->assertNull($charts[2]['value']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertSeeInOrder(['Primary One', 'P.2', 'No marks yet', 'S.1', 'No marks yet']);

        $this->actingAs($admin)->get('/admin/marks/classes')->assertOk()
            ->assertSeeInOrder(['Primary One', 'P.2', 'S.1']);
        $this->actingAs($admin)->get('/admin/marks/classes/'.$second->id)->assertOk()
            ->assertSee('No marks yet');
    }

    public function test_student_portal_shows_the_same_released_marks_as_the_parent_and_days_present(): void
    {
        [$school, $admin, $year, $standard, $term] = $this->school();
        $section = $this->section($school, 'P.4');
        $link = $this->link($school, $year, $standard, $section);
        $student = $this->student($school, $year, $link, 'Amina', 'female');
        $parent = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 7, 'status' => 'active', 'email_verified' => 1,
        ]);
        StudentParentLink::create([
            'school_id' => $school->id, 'parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 1,
        ]);

        $eot = ExamType::create(['name' => 'End of term', 'code' => 'EOT-'.uniqid(), 'contributes_to_report_total' => true]);
        $bot = ExamType::create(['name' => 'Beginning of term', 'code' => 'BOT-'.uniqid(), 'contributes_to_report_total' => false]);
        $released = [];
        foreach (['Mathematics', 'English', 'Science', 'Social Studies'] as $name) {
            $released[] = $this->subjectExamMark($school, $admin, $year, $term, $standard, $section, $link, $student, $eot, $name, 70);
        }
        $hidden = $this->subjectExamMark($school, $admin, $year, $term, $standard, $section, $link, $student, $bot, 'Art', 40);
        MarksRelease::create([
            'school_id' => $school->id,
            'exam_id' => $released[0]->id,
            'scope' => 'school',
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $admin->id,
            'timezone' => 'Africa/Kampala',
        ]);

        // One present session is how a half day is stored: status 1, no fraction column.
        Attendance::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'user_id' => $student->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => 1,
            'recorded_by' => $admin->id,
        ]);

        $studentPage = $this->actingAs($student)->get('/student/dashboard')->assertOk();
        $studentPage->assertSee('MATHEMATICS');
        $studentPage->assertSee('ENGLISH');
        $studentPage->assertSee('SCIENCE');
        $studentPage->assertSee('SOCIAL STUDIES');
        $studentPage->assertSee('>4<', false);
        $studentPage->assertDontSee('ART');
        $studentPage->assertSee('1 day');
        $studentPage->assertDontSee('>0.5<', false);

        $parentPage = $this->actingAs($parent)->get('/parent/dashboard')->assertOk();
        $parentPage->assertSee('MATHEMATICS');
        $parentPage->assertSee('ENGLISH');
        $parentPage->assertSee('SCIENCE');
        $parentPage->assertSee('SOCIAL STUDIES');
        $parentPage->assertSee('>4<', false);
        $parentPage->assertDontSee('ART');

        Attendance::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'user_id' => $student->id,
            'date' => now()->toDateString(),
            'session' => 'afternoon',
            'status' => 1,
            'recorded_by' => $admin->id,
        ]);
        $this->actingAs($student)->get('/student/dashboard')->assertOk()->assertSee('1 day')->assertDontSee('2 days');

        $this->assertNotNull($hidden);
    }

    public function test_notification_panel_opens_beside_the_bell(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        $this->assertStringContainsString('left: 260px !important;', $css);
        $this->assertStringContainsString('bottom: 12px !important;', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 767px\) \{[^}]*#notification-list > \.shadow/s', $css);
        $vue = file_get_contents(base_path('resources/assets/js/components/notification/Show.vue'));
        $this->assertStringContainsString('class="shadow notification-panel"', $vue);
        $this->assertStringNotContainsString('top:0;right:0', $vue);
    }

    /** @return array{0: School, 1: User, 2: AcademicYear, 3: Standard, 4: AcademicTerm} */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Streams', 'slug' => 'streams-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999), 'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'y',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(), 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term III',
            'starts_on' => now()->subMonths(2)->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(), 'status' => 'current',
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $admin = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active']);
        Userprofile::create([
            'user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3,
            'firstname' => 'Grace', 'lastname' => 'Admin', 'status' => 'active',
        ]);

        return [$school, $admin, $year, $standard, $term];
    }

    private function section(School $school, string $name): Section
    {
        return Section::create(['school_id' => $school->id, 'name' => $name, 'status' => 1]);
    }

    private function link(School $school, AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
        ]);
    }

    private function student(School $school, AcademicYear $year, ?StandardLink $link, string $first, string $gender, ?string $kls = null): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active',
            'name' => $first.' '.uniqid(), 'registration_number' => $kls, 'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => 6,
            'firstname' => $first, 'lastname' => 'Pupil', 'gender' => $gender, 'status' => 'active',
        ]);
        if ($link) {
            StudentAcademic::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'user_id' => $user->id, 'standardLink_id' => $link->id,
            ]);
        }

        return $user;
    }

    private function markedExam(School $school, User $teacher, AcademicYear $year, Standard $standard, Section $section, StandardLink $link, bool $withMark = true): Exam
    {
        $type = ExamType::query()->firstOrCreate(
            ['code' => 'EOT'],
            ['name' => 'End of term', 'contributes_to_report_total' => true]
        );
        $term = AcademicTerm::query()->where('school_id', $school->id)->where('status', 'current')->first();
        $subject = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => 'Mathematics', 'code' => 'MTC', 'type' => 'core', 'status' => 1,
        ]);
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'academic_term_id' => $term->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'exam_type_id' => $type->id, 'status' => 'undone',
            'scheduled_at' => now()->subDay(),
        ]));
        if ($withMark) {
            $learner = $this->student($school, $year, $link, 'Marked', 'female');
            Marks::create([
                'student_id' => $learner->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
                'exam_id' => $exam->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
                'marks' => 60, 'grade' => 'C',
            ]);
        }

        return $exam;
    }

    private function subjectExamMark(
        School $school,
        User $teacher,
        AcademicYear $year,
        AcademicTerm $term,
        Standard $standard,
        Section $section,
        StandardLink $link,
        User $student,
        ExamType $type,
        string $name,
        float $score
    ): Exam {
        $subject = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => $name, 'code' => strtoupper(substr($name, 0, 3)).random_int(10, 99),
            'type' => 'core', 'status' => 1,
        ]);
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'academic_year_id' => $year->id, 'academic_term_id' => $term->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'exam_type_id' => $type->id, 'status' => 'done',
        ]));
        Marks::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'exam_id' => $exam->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
            'marks' => $score, 'grade' => 'C',
        ]);

        return $exam;
    }
}
