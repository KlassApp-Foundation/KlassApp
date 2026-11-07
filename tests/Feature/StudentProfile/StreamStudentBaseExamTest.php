<?php

namespace Tests\Feature\StudentProfile;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\StudentProfile\StudentMarksDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A stream student has no exams on the stream section. The twelve exams sit
 * on the base section, which has no enrolments. Record marks must list those
 * base exams.
 *
 * Provenance: user-supplied local-agent read-only findings. Factory ids differ.
 */
class StreamStudentBaseExamTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private User $admin;

    private User $teacher;

    private User $westStudent;

    private User $eastStudent;

    private Section $p7Base;

    private Section $p7West;

    /** @var list<Exam> */
    private array $p7BaseExams = [];

    private Exam $p4Exam;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([[
            'id' => 2, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]], 'id');

        $this->school = $this->makeSchool('Synthetic Marks School');
        $this->otherSchool = $this->makeSchool('Other Marks School');
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'AY',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-18',
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);
        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 7, 'status' => 1,
        ]);
        SchoolGradingSystem::create([
            'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
            'grade' => 'A', 'points' => 1, 'min_score' => 0, 'max_score' => 100, 'remark' => 'All',
        ]);

        $this->admin = $this->user($this->school, 3, 'Marks Admin');
        $this->teacher = $this->user($this->school, 5, 'West Class Teacher');

        $this->p7Base = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Seven', 'status' => 1, 'class_teacher_id' => null,
        ]);
        $p7East = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Seven E', 'status' => 1,
        ]);
        $this->p7West = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Seven W', 'status' => 1, 'class_teacher_id' => $this->teacher->id,
        ]);
        $baseLink = $this->link($this->p7Base, null);
        $eastLink = $this->link($p7East, null);
        $westLink = $this->link($this->p7West, $this->teacher->id);

        $this->enrol($eastLink, 'P7E', 55);
        $this->westStudent = $this->enrol($westLink, 'P7W', 1)[0];
        $this->assertSame(0, StudentAcademic::query()->where('standardLink_id', $baseLink->id)->count());

        for ($i = 1; $i <= 12; $i++) {
            $this->p7BaseExams[] = $this->exam($this->p7Base, sprintf('P7 Paper %02d', $i));
        }
        $this->assertSame(0, Exam::query()->whereIn('section_id', [$p7East->id, $this->p7West->id])->count());

        $p4 = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Four', 'status' => 1, 'class_teacher_id' => null,
        ]);
        $p4East = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Four E', 'status' => 1,
        ]);
        $this->link($p4, null);
        $p4EastLink = $this->link($p4East, null);
        $this->eastStudent = $this->enrol($p4EastLink, 'P4E', 1)[0];
        $this->p4Exam = $this->exam($p4, 'P4 Base Paper');
        for ($i = 2; $i <= 12; $i++) {
            $this->exam($p4, sprintf('P4 Paper %02d', $i));
        }
        $this->assertSame(0, Exam::query()->where('section_id', $p4East->id)->count());
    }

    public function test_record_marks_for_a_stream_student_lists_the_base_class_exams(): void
    {
        $drafts = app(StudentMarksDraftService::class);
        $sittings = $drafts->sittings($this->westStudent);
        $this->assertCount(1, $sittings);
        $subjects = $drafts->subjects($this->westStudent, $sittings[0]['exam_type_id'], $sittings[0]['term_id']);
        $names = array_column($subjects, 'subject');
        $this->assertCount(12, $subjects);
        $this->assertContains('P7 paper 01', $names);
        $this->assertNotContains('P4 base paper', $names);

        $list = $this->actingAs($this->admin)->get('/admin/students?search=P7W')->assertOk()->getContent();
        $destination = '/admin/students/'.$this->westStudent->id.'?action=marks';
        $this->assertStringContainsString($destination, $list);
        $this->assertStringContainsString('Record marks', $list);

        $page = $this->actingAs($this->admin)->get($destination)->assertOk();
        $page->assertSee('P7 paper 01');
        $page->assertSee('P7 paper 12');
        $page->assertDontSee('There are no exams for this class this year yet');
        $page->assertDontSee('P4 base paper');
        $this->assertSame(12, substr_count($page->getContent(), 'name="marks['));

        $p4 = $this->actingAs($this->admin)
            ->get('/admin/students/'.$this->eastStudent->id.'?action=marks')
            ->assertOk();
        $p4->assertSee('P4 base paper');
        $p4->assertDontSee('P7 paper 01');
        $this->assertSame(12, substr_count($p4->getContent(), 'name="marks['));

        $saved = $drafts->save($this->admin, $this->westStudent, $sittings[0]['exam_type_id'], $sittings[0]['term_id'], [
            $this->p7BaseExams[0]->id => '77',
        ]);
        $this->assertSame(1, $saved['saved']);
        $this->assertDatabaseHas('marks', [
            'school_id' => $this->school->id,
            'student_id' => $this->westStudent->id,
            'exam_id' => $this->p7BaseExams[0]->id,
            'marks' => 77,
        ]);

        AcademicYear::create([
            'school_id' => $this->otherSchool->id, 'name' => '2026', 'description' => 'AY',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        Standard::create([
            'school_id' => $this->otherSchool->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->otherSchool->id);
        $otherAdmin = $this->user($this->otherSchool, 3, 'Other Marks Admin');
        $this->actingAs($otherAdmin)->get($destination)->assertNotFound();
        $this->actingAs($this->teacher)->get($destination)->assertRedirect('/teacher/dashboard');
        $this->assertSame(1, Marks::query()->count());
    }

    public function test_a_stream_exam_stays_listed_and_a_sibling_stream_exam_does_not(): void
    {
        $own = $this->exam($this->p7West, 'West Stream Paper');
        $east = Section::query()->where('school_id', $this->school->id)->where('name', 'Primary Seven E')->first();
        $this->exam($east, 'East Only Paper');

        $drafts = app(StudentMarksDraftService::class);
        [$sitting] = $drafts->sittings($this->westStudent);
        $names = array_column($drafts->subjects($this->westStudent, $sitting['exam_type_id'], $sitting['term_id']), 'subject');

        $this->assertContains('P7 paper 01', $names);
        $this->assertContains('West stream paper', $names);
        $this->assertNotContains('East only paper', $names);
        $this->assertSame($own->id, collect($drafts->subjects($this->westStudent, $sitting['exam_type_id'], $sitting['term_id']))->firstWhere('subject', 'West stream paper')['exam_id']);
    }

    private function exam(Section $section, string $subjectName): Exam
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'name' => $subjectName,
            'code' => substr(md5($subjectName.$section->id), 0, 8),
            'type' => 'core',
            'status' => 1,
        ]);

        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'status' => 'undone',
        ]));
    }

    private function link(Section $section, ?int $teacherId): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacherId,
            'stream' => null,
            'status' => 1,
        ]);
    }

    /**
     * @return list<User>
     */
    private function enrol(StandardLink $link, string $side, int $count): array
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = $this->user($this->school, 6, $side.' '.$i);
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'user_id' => $user->id,
                'standardLink_id' => $link->id,
            ]);
            $users[] = $user;
        }

        return $users;
    }

    private function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => 'marks-'.uniqid(),
            'email' => uniqid().'@marks.test',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function user(School $school, int $group, string $name): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@marks.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        $parts = explode(' ', $name, 2);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => $parts[0],
            'lastname' => $parts[1] ?? '',
        ]);

        return $user;
    }
}
