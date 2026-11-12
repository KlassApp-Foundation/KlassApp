<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Another school's exam, mark, class, or learner is the same not-found as a missing id.
 * Teachers never reach the admin pages: the school-admin gate sends them to their dashboard.
 */
class ExamSchoolNotFoundTest extends TestCase
{
    use RefreshDatabase;

    private School $harbour;

    private School $linden;

    private User $harbourAdmin;

    private User $harbourSubjectTeacher;

    private User $harbourLearner;

    private Exam $exam;

    private Marks $harbourMark;

    private Marks $lindenMark;

    private StandardLink $harbourClass;

    private StandardLink $lindenClass;

    private User $lindenLearner;

    /** @var array<string, User> */
    private array $outsiders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            'id' => 1,
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->harbour = $this->school('Cedar Gate School', 'cedar-gate', 'a@cedar-gate.test', '+256700110001');
        $this->linden = $this->school('Linden Gate School', 'linden-gate', 'a@linden-gate.test', '+256700110002');

        $harbourYear = AcademicYear::where('school_id', $this->harbour->id)->first();
        $harbourTerm = AcademicTerm::where('school_id', $this->harbour->id)->first();
        $harbourStandard = Standard::where('school_id', $this->harbour->id)->first();
        $harbourSection = Section::where('school_id', $this->harbour->id)->first();

        $this->harbourAdmin = User::factory()->create([
            'school_id' => $this->harbour->id,
            'usergroup_id' => 3,
            'name' => 'Cira Cedar',
            'email' => 'cira@cedar-gate.test',
        ]);
        $this->harbourSubjectTeacher = User::factory()->create([
            'school_id' => $this->harbour->id,
            'usergroup_id' => 5,
            'name' => 'Ben Cedar',
            'email' => 'ben@cedar-gate.test',
        ]);
        $this->harbourLearner = User::factory()->create([
            'school_id' => $this->harbour->id,
            'usergroup_id' => 6,
            'name' => 'Pia Cedar',
            'email' => 'pia@cedar-gate.test',
            'registration_number' => 'KLS0011001',
        ]);

        $subject = Subject::create([
            'school_id' => $this->harbour->id,
            'academic_year_id' => $harbourYear->id,
            'standard_id' => $harbourStandard->id,
            'section_id' => $harbourSection->id,
            'name' => 'English',
            'type' => 'core',
        ]);

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->harbour->id,
            'academic_year_id' => $harbourYear->id,
            'academic_term_id' => $harbourTerm->id,
            'standard_id' => $harbourStandard->id,
            'section_id' => $harbourSection->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->harbourSubjectTeacher->id,
            'exam_type_id' => 1,
            'status' => 'undone',
            'scheduled_at' => '2031-04-02 09:15:00',
        ]));

        $this->harbourClass = StandardLink::create([
            'school_id' => $this->harbour->id,
            'academic_year_id' => $harbourYear->id,
            'standard_id' => $harbourStandard->id,
            'section_id' => $harbourSection->id,
            'status' => 1,
        ]);

        $this->harbourMark = Marks::create([
            'student_id' => $this->harbourLearner->id,
            'subject_id' => $subject->id,
            'section_id' => $harbourSection->id,
            'exam_id' => $this->exam->id,
            'teacher_id' => $this->harbourSubjectTeacher->id,
            'school_id' => $this->harbour->id,
            'marks' => 61,
            'grade' => 'C',
        ]);

        $lindenYear = AcademicYear::where('school_id', $this->linden->id)->first();
        $lindenStandard = Standard::where('school_id', $this->linden->id)->first();
        $lindenSection = Section::where('school_id', $this->linden->id)->first();
        $lindenTerm = AcademicTerm::where('school_id', $this->linden->id)->first();

        $classTeacher = User::factory()->create([
            'school_id' => $this->linden->id,
            'usergroup_id' => 5,
            'name' => 'Ada Linden',
            'email' => 'ada@linden-gate.test',
        ]);
        $lindenSection->update(['class_teacher_id' => $classTeacher->id]);

        $subjectTeacher = User::factory()->create([
            'school_id' => $this->linden->id,
            'usergroup_id' => 5,
            'name' => 'Ben Linden',
            'email' => 'ben@linden-gate.test',
        ]);
        $lindenSubject = Subject::create([
            'school_id' => $this->linden->id,
            'academic_year_id' => $lindenYear->id,
            'standard_id' => $lindenStandard->id,
            'section_id' => $lindenSection->id,
            'name' => 'Mathematics',
            'type' => 'core',
        ]);
        $lindenExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->linden->id,
            'academic_year_id' => $lindenYear->id,
            'academic_term_id' => $lindenTerm->id,
            'standard_id' => $lindenStandard->id,
            'section_id' => $lindenSection->id,
            'subject_id' => $lindenSubject->id,
            'teacher_id' => $subjectTeacher->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]));

        $this->lindenLearner = User::factory()->create([
            'school_id' => $this->linden->id,
            'usergroup_id' => 6,
            'name' => 'Nia Linden',
            'email' => 'nia@linden-gate.test',
            'registration_number' => 'KLS0011002',
        ]);
        $this->lindenClass = StandardLink::create([
            'school_id' => $this->linden->id,
            'academic_year_id' => $lindenYear->id,
            'standard_id' => $lindenStandard->id,
            'section_id' => $lindenSection->id,
            'class_teacher_id' => $classTeacher->id,
            'status' => 1,
        ]);
        $this->lindenMark = Marks::create([
            'student_id' => $this->lindenLearner->id,
            'subject_id' => $lindenSubject->id,
            'section_id' => $lindenSection->id,
            'exam_id' => $lindenExam->id,
            'teacher_id' => $subjectTeacher->id,
            'school_id' => $this->linden->id,
            'marks' => 88,
            'grade' => 'A',
        ]);

        $this->outsiders = [
            'admin' => User::factory()->create([
                'school_id' => $this->linden->id,
                'usergroup_id' => 3,
                'name' => 'Cira Linden',
                'email' => 'cira@linden-gate.test',
            ]),
            'head' => User::factory()->create([
                'school_id' => $this->linden->id,
                'usergroup_id' => 4,
                'name' => 'Dana Linden',
                'email' => 'dana@linden-gate.test',
            ]),
            'class teacher' => $classTeacher,
            'subject teacher' => $subjectTeacher,
        ];
    }

    #[Test]
    public function another_school_cannot_open_edit_or_archive_the_exam(): void
    {
        $own = $this->actingAs($this->harbourAdmin)->get(route('admin.exams.edit', $this->exam));
        $own->assertOk();
        $own->assertSee('2031-04-02T09:15', false);

        foreach ($this->outsiders as $label => $actor) {
            $edit = $this->actingAs($actor)->get(route('admin.exams.edit', $this->exam));
            $update = $this->actingAs($actor)->put(route('admin.exams.update', $this->exam), ['status' => 'postponed']);
            $archive = $this->actingAs($actor)->delete(route('admin.exams.archive', $this->exam));

            if ((int) $actor->usergroup_id === 5) {
                // The admin gate sends every teacher to their dashboard before the form.
                $edit->assertRedirect('/teacher/dashboard');
                $update->assertRedirect('/teacher/dashboard');
                $archive->assertRedirect('/teacher/dashboard');
            } else {
                $missingEdit = $this->actingAs($actor)->get(route('admin.exams.edit', 99999999));
                $missingUpdate = $this->actingAs($actor)->put(route('admin.exams.update', 99999999), ['status' => 'postponed']);
                $missingArchive = $this->actingAs($actor)->delete(route('admin.exams.archive', 99999999));
                $this->assertSame(404, $missingEdit->status(), $label.' missing edit');
                $this->assertSame($missingEdit->status(), $edit->status(), $label.' edit status');
                $this->assertSame($missingUpdate->status(), $update->status(), $label.' update status');
                $this->assertSame($missingArchive->status(), $archive->status(), $label.' archive status');
            }
            $edit->assertDontSee('2031-04-02T09:15', false);
            $this->assertSame('undone', $this->exam->fresh()->status, $label.' left the exam unchanged');
            $this->assertNotNull(Exam::query()->find($this->exam->id));
        }

        $this->actingAs($this->harbourAdmin)
            ->put(route('admin.exams.update', $this->exam), ['status' => 'postponed'])
            ->assertRedirect(route('admin.exams'));
        $this->assertSame('postponed', $this->exam->fresh()->status);
    }

    #[Test]
    public function another_school_cannot_open_the_mark_the_class_report_or_the_learner_report(): void
    {
        foreach ($this->outsiders as $label => $actor) {
            $download = $this->actingAs($actor)->get(route('admin.reports.cards.download', $this->harbourClass));
            $preview = $this->actingAs($actor)->get(route('admin.reports.cards.student.preview', [$this->harbourClass, $this->harbourLearner]));
            $card = $this->actingAs($actor)->get(route('admin.reports.cards.student.download', [$this->harbourClass, $this->harbourLearner]));
            $foreignLearner = $this->actingAs($this->harbourAdmin)->get(route('admin.reports.cards.student.preview', [$this->harbourClass, $this->lindenLearner]));

            $this->assertRefused($download, $actor, $label.' class download');
            $this->assertRefused($preview, $actor, $label.' preview');
            $this->assertRefused($card, $actor, $label.' download');
            $foreignLearner->assertNotFound();
            $preview->assertDontSee('Pia Cedar', false);
            $foreignLearner->assertDontSee('Nia Linden', false);

            $markPage = $this->actingAs($actor)->get(route('teacher.student.marks.edit', [
                'exam' => $this->exam,
                'student' => $this->harbourLearner,
                'marks' => $this->harbourMark,
            ]));
            $this->assertNotSame(200, $markPage->status(), $label.' mark edit');
            $markPage->assertDontSee('Pia Cedar', false);
            $markPage->assertDontSee('61', false);
        }

        $swapped = $this->actingAs($this->harbourSubjectTeacher)->get(route('teacher.student.marks.edit', [
            'exam' => $this->exam,
            'student' => $this->lindenLearner,
            'marks' => $this->lindenMark,
        ]));
        $swapped->assertNotFound();
        $swapped->assertDontSee('Nia Linden', false);
        $swapped->assertDontSee('88', false);
        $this->assertSame(88, (int) $this->lindenMark->fresh()->marks);

        $this->actingAs($this->harbourSubjectTeacher, 'sanctum')
            ->getJson('/api/v2/exams/upcoming/'.$this->lindenLearner->id)
            ->assertNotFound();
        $this->actingAs($this->harbourSubjectTeacher, 'sanctum')
            ->getJson('/api/v2/exams/past/'.$this->lindenLearner->id)
            ->assertNotFound();

        foreach ($this->outsiders as $label => $actor) {
            $this->actingAs($actor, 'sanctum')
                ->getJson('/api/v2/exams/upcoming/'.$this->harbourLearner->id)
                ->assertNotFound();
            $this->actingAs($actor, 'sanctum')
                ->getJson('/api/v2/exams/past/99999999')
                ->assertNotFound();
            $this->assertNotNull($label);
        }
    }

    private function assertRefused(TestResponse $response, User $actor, string $label): void
    {
        if ((int) $actor->usergroup_id === 5) {
            $response->assertRedirect('/teacher/dashboard');

            return;
        }

        $response->assertNotFound();
        $this->assertNotSame('', $label);
    }

    private function school(string $name, string $slug, string $email, string $phone): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026 Test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);

        return $school;
    }
}
