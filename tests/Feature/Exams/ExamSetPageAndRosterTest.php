<?php

namespace Tests\Feature\Exams;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamSet;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Academics\ExamSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The set page, and marks entry when the exam sits on a base class whose
 * students live on its streams.
 */
class ExamSetPageAndRosterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $base;

    private Section $east;

    private Section $west;

    private User $admin;

    private User $baseTeacher;

    private User $eastTeacher;

    private User $classHead;

    private User $amina;

    private User $brian;

    private Subject $baseMaths;

    private ExamType $eot;

    private Exam $baseExam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Riverside Roster', 'slug' => 'riverside-roster', 'email' => 'roster@test.ug',
            'phone' => '0700000011', 'status' => 1,
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01',
        ]);
        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 2, 'status' => 1,
        ]);
        $this->base = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One', 'status' => 1]);
        $this->east = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One E', 'status' => 1]);
        $this->west = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One W', 'status' => 1]);

        $this->admin = $this->makeUser(3, 'School Admin');
        $this->baseTeacher = $this->makeUser(5, 'Base Teacher');
        $this->eastTeacher = $this->makeUser(5, 'East Teacher');
        $this->classHead = $this->makeUser(5, 'Class Head');
        $this->amina = $this->makeUser(6, 'Amina East', 'Amina', 'East');
        $this->brian = $this->makeUser(6, 'Brian West', 'Brian', 'West');

        $this->base->class_teacher_id = $this->classHead->id;
        $this->base->save();

        $this->baseMaths = $this->makeSubject($this->base, 'Mathematics');
        $eastMaths = $this->makeSubject($this->east, 'Mathematics');
        $westMaths = $this->makeSubject($this->west, 'Mathematics');

        $baseLink = $this->link($this->base, $this->baseMaths, $this->baseTeacher, null);
        $this->link($this->east, $eastMaths, $this->eastTeacher, null);
        $this->link($this->west, $westMaths, $this->makeUser(5, 'West Teacher'), null);
        StandardLink::query()->whereKey($baseLink)->update(['class_teacher_id' => $this->classHead->id]);

        $this->enroll($this->amina, $this->east);
        $this->enroll($this->brian, $this->west);

        $this->eot = ExamType::create([
            'school_id' => $this->school->id,
            'name' => 'End of Term Examination', 'code' => 'EOT', 'contributes_to_report_total' => true,
        ]);

        app(ExamSetService::class)->create(
            $this->school, $this->term, [$this->eot->id], [$this->base->id], $this->admin
        );
        $this->baseExam = Exam::query()->where('school_id', $this->school->id)->where('section_id', $this->base->id)->firstOrFail();
    }

    public function test_store_redirects_to_the_set_page_grouped_by_class_and_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/exams/sets', [
                'academic_term_id' => $this->term->id,
                'type_ids' => [$this->eot->id],
                'standard_ids' => [$this->base->id],
            ]);

        $set = ExamSet::query()->where('school_id', $this->school->id)->orderByDesc('id')->firstOrFail();
        $response->assertRedirect(route('admin.exam-sets.show', ['batch' => $set->id]));
        $this->assertNotSame((int) $this->baseExam->exam_set_id, (int) $set->id, 'a second run skips the exam that already exists');

        $first = ExamSet::query()->where('school_id', $this->school->id)->orderBy('id')->firstOrFail();
        $html = $this->actingAs($this->admin)
            ->get(route('admin.exam-sets.show', ['batch' => $first->id]))
            ->assertOk()
            ->assertSee('Primary One', false)
            ->assertSee('End of Term Examination', false)
            ->assertSee('MATHEMATICS', false)
            ->assertSee('Base Teacher', false)
            ->getContent();

        $this->assertStringContainsString('data-testid="exam-set-class-'.$this->base->id.'"', $html);
        $this->assertStringContainsString('data-testid="exam-set-teacher-'.$this->baseExam->id.'"', $html);
        $this->assertStringContainsString('1 exam', $html);
        $this->assertStringContainsString('data-testid="exam-set-link-'.$first->id.'"', $html);
        $this->assertStringContainsString('data-testid="exam-set-term-term-3"', $html);
        $this->assertStringContainsString(route('admin.exam-sets.show', ['batch' => $set->id]), $html);
        $this->assertStringContainsString('data-testid="exam-set-kpis"', $html);
        $this->assertStringContainsString('Current term', $html);
    }

    public function test_exam_set_list_uses_the_empty_state_and_create_page_names_the_failure(): void
    {
        \App\Models\Academics\Exam::query()->where('school_id', $this->school->id)->delete();
        \App\Models\Academics\ExamSet::query()->where('school_id', $this->school->id)->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.exam-sets.index'))
            ->assertOk()
            ->assertSee('No exams set for', false)
            ->assertSee('New exam set', false)
            ->assertSee('>Filters<', false);

        $this->actingAs($this->admin)
            ->get(route('admin.exam-sets.create'))
            ->assertOk()
            ->assertSee('New exam set', false)
            ->assertSee("Create this term's exam set", false)
            ->assertSee('data-testid="exam-set-creating"', false)
            ->assertSee('You can leave this page', false);
    }

    public function test_a_base_class_exam_lists_students_from_every_stream(): void
    {
        $html = $this->actingAs($this->baseTeacher)
            ->get(route('teacher.exam.marks.enter', $this->baseExam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="mark-student-'.$this->amina->id.'"', $html);
        $this->assertStringContainsString('data-testid="mark-student-'.$this->brian->id.'"', $html);
        $this->assertStringContainsString('data-testid="mark-stream-'.$this->amina->id.'">E', $html);
        $this->assertStringContainsString('data-testid="mark-stream-'.$this->brian->id.'">W', $html);
        $this->assertStringContainsString('data-testid="marks-entry-stream-filter"', $html);
    }

    public function test_a_stream_subject_teacher_sees_only_their_stream_and_cannot_save_another(): void
    {
        $html = $this->actingAs($this->eastTeacher)
            ->get(route('teacher.exam.marks.enter', $this->baseExam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="mark-student-'.$this->amina->id.'"', $html);
        $this->assertStringNotContainsString('data-testid="mark-student-'.$this->brian->id.'"', $html);

        $this->actingAs($this->eastTeacher)
            ->post(route('teacher.exam.marks.save', $this->baseExam), [
                'marks' => [$this->brian->id => 40],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('marks', [
            'exam_id' => $this->baseExam->id,
            'student_id' => $this->brian->id,
        ]);

        $this->actingAs($this->eastTeacher)
            ->get(route('teacher.exam.marks.enter', ['exam' => $this->baseExam, 'stream' => $this->west->id]))
            ->assertForbidden();

        $this->actingAs($this->eastTeacher)
            ->post(route('teacher.exam.marks.save', $this->baseExam), [
                'marks' => [$this->amina->id => 71],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->baseExam));

        $this->assertSame(71, (int) Marks::query()->where('exam_id', $this->baseExam->id)->where('student_id', $this->amina->id)->value('marks'));
    }

    public function test_the_class_head_sees_and_enters_marks_for_every_stream(): void
    {
        $headed = app(\App\Services\ExamAuthorization::class)->sectionIdsForClassTeacher(
            $this->classHead, (int) $this->school->id, (int) $this->year->id
        );
        $this->assertContains($this->base->id, $headed);

        $all = $this->actingAs($this->classHead)
            ->get(route('teacher.exam.marks.enter', $this->baseExam))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="mark-student-'.$this->amina->id.'"', $all);
        $this->assertStringContainsString('data-testid="mark-student-'.$this->brian->id.'"', $all);

        $eastOnly = $this->actingAs($this->classHead)
            ->get(route('teacher.exam.marks.enter', ['exam' => $this->baseExam, 'stream' => $this->east->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="mark-student-'.$this->amina->id.'"', $eastOnly);
        $this->assertStringNotContainsString('data-testid="mark-student-'.$this->brian->id.'"', $eastOnly);

        $this->actingAs($this->classHead)
            ->post(route('teacher.exam.marks.save', $this->baseExam), [
                'marks' => [
                    $this->amina->id => 80,
                    $this->brian->id => 64,
                ],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $this->baseExam));

        $this->assertSame(80, (int) Marks::query()->where('exam_id', $this->baseExam->id)->where('student_id', $this->amina->id)->value('marks'));
        $this->assertSame(64, (int) Marks::query()->where('exam_id', $this->baseExam->id)->where('student_id', $this->brian->id)->value('marks'));
    }

    public function test_a_markless_exam_can_be_removed_and_an_exam_with_marks_cannot(): void
    {
        $setId = (int) $this->baseExam->exam_set_id;
        $english = $this->makeSubject($this->base, 'English');
        $this->link($this->base, $english, $this->baseTeacher, null);
        $marked = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->base->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $english->id,
            'teacher_id' => $this->baseTeacher->id,
            'exam_type_id' => $this->eot->id,
            'exam_set_id' => $setId,
            'status' => 'undone',
        ]);
        Marks::create([
            'student_id' => $this->amina->id,
            'subject_id' => $english->id,
            'section_id' => $this->base->id,
            'exam_id' => $marked->id,
            'teacher_id' => $this->baseTeacher->id,
            'school_id' => $this->school->id,
            'marks' => 55,
            'grade' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.exam-sets.exams.destroy', ['batch' => $setId, 'exam' => $marked->id]))
            ->assertStatus(422);
        $this->assertDatabaseHas('exams', ['id' => $marked->id]);

        $page = $this->actingAs($this->admin)
            ->get(route('admin.exam-sets.show', ['batch' => $setId]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="exam-set-locked-'.$marked->id.'"', $page);
        $this->assertStringNotContainsString('data-testid="exam-set-remove-'.$marked->id.'"', $page);
        $this->assertStringContainsString('data-testid="exam-set-remove-'.$this->baseExam->id.'"', $page);

        $this->actingAs($this->admin)
            ->delete(route('admin.exam-sets.exams.destroy', ['batch' => $setId, 'exam' => $this->baseExam->id]))
            ->assertRedirect(route('admin.exam-sets.show', ['batch' => $setId]));
        $this->assertDatabaseMissing('exams', ['id' => $this->baseExam->id]);

        $other = School::create([
            'name' => 'Other School', 'slug' => 'other-roster', 'email' => 'other-roster@test.ug',
            'phone' => '0700000012', 'status' => 1,
        ]);
        $outsider = User::create([
            'school_id' => $other->id, 'usergroup_id' => 3, 'name' => 'Other Admin',
            'email' => 'other-admin-'.uniqid().'@test.ug', 'password' => bcrypt('secret'),
        ]);
        $this->actingAs($outsider)
            ->get(route('admin.exam-sets.show', ['batch' => $setId]))
            ->assertNotFound();
    }

    public function test_a_stream_level_exam_still_lists_only_that_stream(): void
    {
        $streamSubject = Subject::query()->where('section_id', $this->east->id)->where('name', 'Mathematics')->firstOrFail();
        $streamExam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->east->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $streamSubject->id,
            'teacher_id' => $this->eastTeacher->id,
            'exam_type_id' => $this->eot->id,
            'status' => 'undone',
        ]);

        $html = $this->actingAs($this->eastTeacher)
            ->get(route('teacher.exam.marks.enter', $streamExam))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-testid="mark-student-'.$this->amina->id.'"', $html);
        $this->assertStringNotContainsString('data-testid="mark-student-'.$this->brian->id.'"', $html);
        $this->assertStringNotContainsString('data-testid="marks-entry-stream-filter"', $html);

        $this->actingAs($this->eastTeacher)
            ->post(route('teacher.exam.marks.save', $streamExam), [
                'marks' => [$this->amina->id => 66],
            ])
            ->assertRedirect(route('teacher.exam.marks.enter', $streamExam));

        $this->assertSame(66, (int) Marks::query()->where('exam_id', $streamExam->id)->where('student_id', $this->amina->id)->value('marks'));
    }

    public function test_marks_screen_filters_are_optional_and_exam_sets_stay_out_of_marks(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.marks.filter', ['term' => '', 'class' => '', 'examType' => '']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<select name="term" id="term" class="ds-form-select">', $html);
        $this->assertStringContainsString('<select name="class" id="class" class="ds-form-select">', $html);
        $this->assertStringContainsString('<select name="examType" id="examType" class="ds-form-select">', $html);
        $this->assertStringContainsString('data-testid="marks-filters-open"', $html);
        $this->assertStringContainsString('data-ka-filters', $html);
        $this->assertStringContainsString('>Filters<', $html);
        $this->assertStringNotContainsString('id="term" class="ds-form-select" required', $html);
        $this->assertStringNotContainsString('id="class" class="ds-form-select" required', $html);
        $this->assertStringNotContainsString('id="examType" class="ds-form-select" required', $html);

        $children = collect(config('navigation.roles.admin.sections'))
            ->flatMap(fn (array $section) => $section['rows'] ?? [])
            ->firstWhere('label', 'Exams and reports')['children'];
        $sets = collect($children)->firstWhere('label', 'Exam sets');
        $marks = collect($children)->firstWhere('label', 'Exams and marks');
        $this->assertSame('admin/exams/sets', $sets['url']);
        $this->assertSame('admin/marks', $marks['url']);
        foreach ($marks['paths'] as $pattern) {
            $this->assertFalse(Str::is($pattern, 'admin/exams/sets'));
            $this->assertFalse(Str::is($pattern, 'admin/exams/sets/'.$this->baseExam->exam_set_id));
        }
        foreach ($sets['paths'] as $pattern) {
            $this->assertFalse(Str::is($pattern, 'admin/marks'));
        }
    }

    private function makeUser(int $group, string $name, ?string $first = null, ?string $last = null): User
    {
        $user = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@test.ug',
            'password' => bcrypt('secret'),
        ]);
        Userprofile::create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'usergroup_id' => $group,
            'firstname' => $first ?? $name, 'lastname' => $last ?? 'User',
        ]);

        return $user;
    }

    private function makeSubject(Section $section, string $name): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $section->id,
            'name' => $name, 'code' => substr($name, 0, 3).$section->id, 'type' => 'core', 'status' => 1,
        ]);
    }

    private function link(Section $section, Subject $subject, User $teacher, ?int $classTeacherId): int
    {
        $linkId = (int) DB::table('standards_link')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $section->id,
            'class_teacher_id' => $classTeacherId, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $linkId, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);

        return $linkId;
    }

    private function enroll(User $student, Section $section): void
    {
        $linkId = (int) DB::table('standards_link')
            ->where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('academic_year_id', $this->year->id)
            ->value('id');

        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'user_id' => $student->id, 'standardLink_id' => $linkId,
        ]);
    }
}
