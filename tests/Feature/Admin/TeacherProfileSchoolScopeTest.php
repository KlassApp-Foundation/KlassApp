<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\TeacherProfileTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherProfileSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->school('Teacher Profile A');
        $this->schoolB = $this->school('Teacher Profile B');
        $this->classIn($this->schoolA, 'P.1');
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
        ]);
    }

    public function test_empty_teacher_shows_honest_states_and_hides_unbuilt_tabs(): void
    {
        $this->assertSame(['timetable', 'leave', 'activity'], TeacherProfileTabs::DEFERRED);
        $this->assertSame(['overview', 'teaches'], TeacherProfileTabs::VISIBLE);

        $teacher = $this->teacher($this->schoolA, 'SARAH', 'NAKATO', [
            'gender' => null,
            'mobile_no' => null,
            'email' => null,
        ]);

        $this->actingAs($this->adminA)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertOk()
            ->assertSee('Sarah Nakato')
            ->assertSee('data-testid="person-role"', false)
            ->assertSee('Teacher')
            ->assertSee('Active')
            ->assertSee('No classes yet')
            ->assertSee('No subjects yet')
            ->assertSee('No marks to enter')
            ->assertSee('Not specified')
            ->assertSee('Not recorded')
            ->assertSee('Never')
            ->assertSee('data-tab="overview"', false)
            ->assertSee('data-tab="teaches"', false)
            ->assertDontSee('data-tab="timetable"', false)
            ->assertDontSee('data-tab="leave"', false)
            ->assertDontSee('data-tab="activity"', false)
            ->assertDontSee('Profession')
            ->assertDontSee('Annual income')
            ->assertDontSee('Organization')
            ->assertDontSee('Aadhaar')
            ->assertDontSee('Pincode')
            ->assertSee('/admin/teacher/edit/'.$teacher->name, false)
            ->assertSee('min-height:44px', false)
            ->assertSee('Deactivate this teacher?')
            ->assertDontSee('/admin/teacher/delete/'.$teacher->name, false);
    }

    public function test_teaching_load_uses_this_schools_section_and_latest_exam(): void
    {
        $built = $this->classIn($this->schoolA, 'P.4');
        $otherClass = $this->classIn($this->schoolA, 'P.5', $built['standard'], $built['year']);
        $teacher = $this->teacher($this->schoolA, 'Sarah', 'Nakato', [
            'gender' => 'female',
            'employee_id' => 'EMP-14',
            'designation' => 'senior_teacher',
            'job_type' => 'full_time',
            'date_of_birth' => '1990-04-12',
            'joining_date' => '2024-09-03',
            'mobile_no' => '700123456',
            'email' => 'sarah.nakato@t.sch.ug',
            'year_id' => $built['year']->id,
        ]);
        $login = ActivityLog::create([
            'log_name' => 'login',
            'description' => 'login',
            'causer_id' => $teacher->id,
            'causer_type' => User::class,
            'school_id' => $this->schoolA->id,
        ]);
        $login->created_at = '2026-10-01 09:15:00';
        $login->save();

        $active = [
            $this->learner($this->schoolA, $built, 'Amina'),
            $this->learner($this->schoolA, $built, 'Peter'),
        ];
        $this->learner($this->schoolA, $built, 'Left', 'inactive');
        $elsewhere = [];
        for ($i = 0; $i < 9; $i++) {
            $elsewhere[] = $this->learner($this->schoolA, $otherClass, 'Other'.$i);
        }

        $math = $this->subject($this->schoolA, $built, 'Mathematics', 'MATH');
        $science = $this->subject($this->schoolA, $built, 'Science', 'SCI');
        $this->assign($this->schoolA, $built, $teacher, $math);
        $this->assign($this->schoolA, $built, $teacher, $science);
        $duplicateClass = StandardLink::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $built['year']->id,
            'standard_id' => $built['standard']->id,
            'section_id' => $built['section']->id,
            'status' => 1,
        ]);
        $this->assign($this->schoolA, ['year' => $built['year'], 'link' => $duplicateClass], $teacher, $science);

        $type = ExamType::create([
            'name' => 'End of term',
            'code' => 'EOT',
            'contributes_to_report_total' => true,
        ]);
        $oldMath = $this->exam($this->schoolA, $built, $math, $teacher, $type);
        $newMath = $this->exam($this->schoolA, $built, $math, $teacher, $type);
        $scienceExam = $this->exam($this->schoolA, $built, $science, $teacher, $type);
        $this->exam($this->schoolA, $otherClass, $math, $teacher, $type);
        foreach ($active as $student) {
            $this->mark($this->schoolA, $newMath, $student, $built, $teacher, 80);
        }
        $this->mark($this->schoolA, $scienceExam, $active[0], $built, $teacher, 70);

        $otherSchool = $this->classIn($this->schoolB, 'P.4');
        $intruder = $this->teacher($this->schoolB, 'Sarah', 'Nakato', [
            'employee_id' => 'SECRET-99',
            'year_id' => $otherSchool['year']->id,
        ]);
        $secret = $this->subject($this->schoolB, $otherSchool, 'Secret Subject', 'SEC');
        $this->assign($this->schoolB, $otherSchool, $intruder, $secret);

        $this->actingAs($this->adminA)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertOk()
            ->assertSee('Sarah Nakato')
            ->assertSee('Senior teacher')
            ->assertSee('Joined 3 Sep 2024')
            ->assertSee('Female')
            ->assertSee('EMP-14')
            ->assertSee('Full time')
            ->assertSee('12 Apr 1990')
            ->assertSee('3 Sep 2024')
            ->assertSee('700123456')
            ->assertSee('sarah.nakato@t.sch.ug')
            ->assertSee('1 Oct 2026')
            ->assertSee('data-testid="kpi-classes"', false)
            ->assertSee('>1<', false)
            ->assertSee('P.4')
            ->assertSee('Mathematics')
            ->assertSee('Science')
            ->assertSee('data-pending="1"', false)
            ->assertDontSee('SECRET-99')
            ->assertDontSee('SECRET SUBJECT')
            ->assertDontSee('Secret Subject');

        $html = $this->actingAs($this->adminA)
            ->get('/admin/teacher/show/'.$teacher->name.'?tab=teaches')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-teach="mathematics"[^>]*data-students="2"[^>]*data-marks="entered"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-teach="science"[^>]*data-students="2"[^>]*data-marks="pending"/',
            $html
        );
        $this->assertSame(1, preg_match_all('/data-teach="science"/', $html));
        $this->assertDoesNotMatchRegularExpression('/data-students="11"/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-students="3"/', $html);
        $this->assertTrue($oldMath->id < $newMath->id);
        $this->assertNotEmpty($elsewhere);
    }

    public function test_another_schools_teacher_is_not_visible_or_deactivated(): void
    {
        $teacher = $this->teacher($this->schoolB, 'Other', 'School');

        $this->actingAs($this->adminA)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->post('/admin/teacher/show/'.$teacher->name.'/deactivate')
            ->assertNotFound();

        $this->assertSame('active', $teacher->fresh()->status);
        $this->assertModelExists($teacher);
    }

    public function test_deactivate_flags_inactive_and_keeps_the_record(): void
    {
        $teacher = $this->teacher($this->schoolA, 'Sarah', 'Nakato');

        $this->actingAs($this->adminA)
            ->post('/admin/teacher/show/'.$teacher->name.'/deactivate')
            ->assertRedirect('/admin/teacher/show/'.$teacher->name);

        $fresh = $teacher->fresh();
        $this->assertModelExists($fresh);
        $this->assertSame('inactive', $fresh->status);
        $this->assertSame('inactive', $fresh->userprofile->status);

        $this->actingAs($this->adminA)
            ->get('/admin/teacher/show/'.$teacher->name)
            ->assertOk()
            ->assertSee('Inactive')
            ->assertSee('bindTeacherMore', false);
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function teacher(School $school, string $first, string $last, array $extra = []): User
    {
        $yearId = $extra['year_id'] ?? AcademicYear::query()->where('school_id', $school->id)->value('id');
        if (! $yearId) {
            $yearId = AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026',
                'description' => 'y',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ])->id;
        }

        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => strtolower($first).'-'.uniqid(),
            'email' => $extra['email'] ?? uniqid().'@t.sch.ug',
            'mobile_no' => $extra['mobile_no'] ?? '700000001',
            'status' => 'active',
        ]);
        if (array_key_exists('email', $extra) && $extra['email'] === null) {
            $user->email = null;
            $user->save();
        }
        if (array_key_exists('mobile_no', $extra) && $extra['mobile_no'] === null) {
            $user->mobile_no = null;
            $user->save();
        }

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'firstname' => $first,
            'lastname' => $last,
            'gender' => $extra['gender'] ?? null,
            'date_of_birth' => $extra['date_of_birth'] ?? null,
            'joining_date' => $extra['joining_date'] ?? null,
            'status' => 'active',
        ]);

        if (isset($extra['employee_id']) || isset($extra['designation']) || isset($extra['job_type'])) {
            TeacherProfile::create([
                'school_id' => $school->id,
                'academic_year_id' => $yearId,
                'user_id' => $user->id,
                'employee_id' => $extra['employee_id'] ?? null,
                'designation' => $extra['designation'] ?? 'teacher',
                'job_type' => $extra['job_type'] ?? 'full_time',
                'status' => 1,
            ]);
        }

        return $user->fresh(['userprofile']);
    }

    /**
     * @return array{year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink}
     */
    private function classIn(School $school, string $sectionName, ?Standard $standard = null, ?AcademicYear $year = null): array
    {
        $year ??= AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first()
            ?? AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026',
                'description' => 'y',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);
        $term = AcademicTerm::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->first()
            ?? AcademicTerm::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'name' => 'Term I',
                'starts_on' => '2026-02-01',
                'ends_on' => '2026-04-30',
                'status' => 'current',
            ]);
        $standard ??= Standard::query()
            ->where('school_id', $school->id)
            ->where('name', 'primary')
            ->first()
            ?? Standard::create([
                'school_id' => $school->id,
                'name' => 'primary',
                'order' => 1,
                'status' => 1,
            ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => $sectionName,
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        return compact('year', 'term', 'standard', 'section', 'link');
    }

    /**
     * @param  array{year: AcademicYear, link: StandardLink}  $class
     */
    private function learner(School $school, array $class, string $first, string $status = 'active'): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => $status,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => 'Learner',
            'status' => $status,
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $class['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $class['link']->id,
        ]);

        return $user;
    }

    /**
     * @param  array{year: AcademicYear, standard: Standard, section: Section}  $class
     */
    private function subject(School $school, array $class, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $class['year']->id,
            'standard_id' => $class['standard']->id,
            'section_id' => $class['section']->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
        ]);
    }

    /**
     * @param  array{year: AcademicYear, link: StandardLink}  $class
     */
    private function assign(School $school, array $class, User $teacher, Subject $subject): Teacherlink
    {
        return Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $class['year']->id,
            'standardLink_id' => $class['link']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 1,
        ]);
    }

    /**
     * @param  array{year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section}  $class
     */
    private function exam(School $school, array $class, Subject $subject, User $teacher, ExamType $type): Exam
    {
        $exam = new Exam([
            'school_id' => $school->id,
            'standard_id' => $class['standard']->id,
            'section_id' => $class['section']->id,
            'academic_year_id' => $class['year']->id,
            'academic_term_id' => $class['term']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'done',
        ]);
        $exam->exam_type_id = $type->id;
        $exam->save();

        return $exam;
    }

    /**
     * @param  array{section: Section}  $class
     */
    private function mark(School $school, Exam $exam, User $student, array $class, User $teacher, int $score): void
    {
        Marks::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'school_id' => $school->id,
            'subject_id' => $exam->subject_id,
            'exam_id' => $exam->id,
            'section_id' => $class['section']->id,
            'marks' => $score,
            'grade' => 'D2',
        ]);
    }
}
