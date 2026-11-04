<?php

namespace Tests\Feature\Exams;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Academics\ExamSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamSetServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $p1a;

    private Section $p1b;

    private User $admin;

    private User $mathTeacher;

    private User $englishTeacher;

    private ExamType $eot;

    private ExamType $bot;

    private ExamType $otherSchoolType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Riverside Test', 'slug' => 'riverside-test', 'email' => 'rs@test.ug',
            'phone' => '0700000001', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        // A school observer may seed its own year; the marks list reads the one
        // flagged status = 1, so keep ours the only current year.
        AcademicYear::where('school_id', $this->school->id)->where('id', '!=', $this->year->id)->update(['status' => 0]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term I', 'status' => 'current', 'starts_on' => '2026-02-01', 'ends_on' => '2026-05-01',
        ]);

        $primary = Standard::create(['school_id' => $this->school->id, 'name' => 'Primary', 'order' => 1, 'status' => 1]);
        $this->p1a = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One A', 'status' => 1]);
        $this->p1b = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One B', 'status' => 1]);

        $this->admin = $this->makeUser(3, 'Admin');
        $this->mathTeacher = $this->makeUser(5, 'Maths Teacher');
        $this->englishTeacher = $this->makeUser(5, 'English Teacher');

        $mathA = $this->makeSubject($primary, $this->p1a, 'Mathematics');
        $englishA = $this->makeSubject($primary, $this->p1a, 'Kiswahili');
        $mathB = $this->makeSubject($primary, $this->p1b, 'Mathematics');

        $nursery = Standard::create(['school_id' => $this->school->id, 'name' => 'Nursery', 'order' => 0, 'status' => 1]);
        $baby = Section::create(['school_id' => $this->school->id, 'name' => 'Baby Class', 'status' => 1]);
        $this->makeSubject($nursery, $baby, 'Play');

        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->standardLink($primary, $this->p1a),
            'subject_id' => $mathA->id, 'teacher_id' => $this->mathTeacher->id,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->standardLink($primary, $this->p1a),
            'subject_id' => $englishA->id, 'teacher_id' => $this->englishTeacher->id,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->standardLink($primary, $this->p1b),
            'subject_id' => $mathB->id, 'teacher_id' => $this->mathTeacher->id,
        ]);

        $this->eot = ExamType::create(['name' => 'End of Term Examination', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $this->bot = ExamType::create(['name' => 'Beginning of Term Examination', 'code' => 'BOT', 'contributes_to_report_total' => false]);

        $otherSchool = School::create([
            'name' => 'Other School', 'slug' => 'other-school', 'email' => 'o@test.ug',
            'phone' => '0700000002', 'status' => 1,
        ]);
        $this->otherSchoolType = ExamType::create([
            'school_id' => $otherSchool->id, 'name' => 'Other School Special', 'code' => 'OSS',
            'contributes_to_report_total' => false,
        ]);
    }

    public function test_whole_school_set_creates_every_subject_and_type_and_rerunning_adds_nothing(): void
    {
        $service = app(ExamSetService::class);

        $first = $service->create($this->school, $this->term, [$this->eot->id, $this->bot->id], null, $this->admin);
        $this->assertSame(8, $first['created'], 'four subject rows x two types');
        $this->assertSame(0, $first['skipped']);
        $this->assertSame(3, $first['sections']);
        $this->assertSame(8, Exam::where('school_id', $this->school->id)->count());

        $second = $service->create($this->school, $this->term, [$this->eot->id, $this->bot->id], null, $this->admin);
        $this->assertSame(0, $second['created']);
        $this->assertSame(8, $second['skipped']);
        $this->assertSame(8, Exam::where('school_id', $this->school->id)->count());
    }

    public function test_exams_are_created_for_the_subject_teacher(): void
    {
        app(ExamSetService::class)->create($this->school, $this->term, [$this->eot->id], null, $this->admin);

        $this->assertSame(
            2,
            Exam::where('school_id', $this->school->id)->where('teacher_id', $this->mathTeacher->id)->count(),
            'maths in both streams is the maths teacher\'s',
        );
        $this->assertSame(
            1,
            Exam::where('school_id', $this->school->id)->where('teacher_id', $this->englishTeacher->id)->count(),
        );
    }

    public function test_school_owned_types_are_visible_only_to_their_school(): void
    {
        $mine = ExamType::visibleToSchool($this->school->id)->pluck('id')->all();

        $this->assertContains($this->eot->id, $mine, 'shared types are visible');
        $this->assertContains($this->bot->id, $mine);
        $this->assertNotContains($this->otherSchoolType->id, $mine, 'another school\'s type must not leak');
    }

    public function test_teacher_sees_only_their_own_exams_under_marks(): void
    {
        app(ExamSetService::class)->create($this->school, $this->term, [$this->eot->id], null, $this->admin);

        $this->actingAs($this->mathTeacher)
            ->get('/teacher/exam/marks')
            ->assertOk()
            ->assertSee('MATHEMATICS')
            ->assertDontSee('KISWAHILI');
    }

    public function test_a_class_teacher_creates_a_set_only_for_their_own_class(): void
    {
        $this->p1a->forceFill(['class_teacher_id' => $this->mathTeacher->id])->save();

        $this->actingAs($this->mathTeacher)
            ->post('/teacher/exam/sets', [
                'academic_term_id' => $this->term->id,
                'type_ids' => [$this->eot->id],
                'standard_ids' => [\App\Models\Standard::where('school_id', $this->school->id)->where('name', 'Primary')->value('id')],
            ])
            ->assertRedirect(route('teacher.exam.marks'));

        $this->assertSame(
            2,
            Exam::where('school_id', $this->school->id)->where('section_id', $this->p1a->id)->count(),
            'their class\'s streams: three subject rows',
        );
        // The class is the unit: its sibling stream is included, nothing outside it.
        $this->assertSame(3, Exam::where('school_id', $this->school->id)->count());
    }

    public function test_admin_empty_state_points_at_the_exam_set(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/marks')
            ->assertOk()
            ->assertSee("No exams yet: create this term's exam set", false)
            ->assertSee(route('admin.exam-sets.create'));
    }

    public function test_admin_can_open_the_exam_set_form(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/exams/sets/create')
            ->assertOk()
            ->assertSee('Create this term\'s exam set', false)
            ->assertSee('End of Term Examination');
    }

    private function makeUser(int $group, string $name): User
    {
        $user = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@test.ug',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'usergroup_id' => $group,
            'firstname' => $name, 'lastname' => 'User',
        ]);

        return $user;
    }

    private function makeSubject(Standard $standard, Section $section, string $name): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => $name, 'code' => substr($name, 0, 3), 'type' => 'core', 'status' => 1,
        ]);
    }

    private function standardLink(Standard $standard, Section $section): int
    {
        return (int) DB::table('standards_link')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
