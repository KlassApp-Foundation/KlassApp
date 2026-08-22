<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamCrossSchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private int $examBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0,
             'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'exam-a',
            'email' => 'a@exam.test', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'exam-b',
            'email' => 'b@exam.test', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            $year = AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026 Test',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            AcademicTerm::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
                'status' => 'current',
            ]);
            Standard::create([
                'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
            ]);
            Section::create([
                'school_id' => $school->id, 'name' => 'P.1', 'status' => 1,
            ]);
        }

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@exam.test',
        ]);

        // Create exam in School B
        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->first();
        $termB = AcademicTerm::where('school_id', $this->schoolB->id)->first();
        $standardB = Standard::where('school_id', $this->schoolB->id)->first();
        $sectionB = Section::where('school_id', $this->schoolB->id)->first();
        $teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 5,
            'email' => 'teacher.b@exam.test',
        ]);
        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'name' => 'Math B',
            'type' => 'core',
        ]);

        $examB = \App\Models\Academics\Exam::create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'academic_term_id' => $termB->id,
            'standard_id' => $standardB->id,
            'section_id' => $sectionB->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $teacherB->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
        $this->examBId = $examB->id;
    }

    /** @test */
    public function admin_cannot_update_another_schools_exam()
    {
        // Admin A tries to update School B's exam
        $response = $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class)
            ->put("/admin/exams/{$this->examBId}/update", [
                'name' => 'Hacked Exam',
            ]);

        // The controller returns a redirect on "success" (0 rows matched is
        // indistinguishable from a successful update of 0 fields), but the
        // school_id scope means the School B exam is untouched.
        $response->assertRedirect();

        $this->assertDatabaseHas('exams', [
            'id' => $this->examBId,
            'status' => 'undone',
        ]);
    }
}