<?php

namespace Tests\Feature;

use App\Http\Resources\Exam as ExamResource;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionListExamTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_exams_table_has_exam_type_id_not_exam_type_column(): void
    {
        $this->assertFalse(Schema::hasColumn('exams', 'exam_type'));
        $this->assertTrue(Schema::hasColumn('exams', 'exam_type_id'));
    }

    public function test_promotion_exam_query_filters_by_final_exam_type_relation(): void
    {
        [$school, $year, $standard, $finalExam] = $this->seedPromotionExamFixture();

        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);
        Auth::login($admin);

        // Same filter shape as PromotionController@index after the exam_type fix.
        $exams = Exam::query()
            ->with(['examType', 'subject'])
            ->where('school_id', Auth::user()->school_id)
            ->where('academic_year_id', $year->id)
            ->whereRelation('examType', 'code', 'FINAL')
            ->get();

        $grouped = ExamResource::collection($exams)->groupby('standard_id');

        $this->assertCount(1, $exams);
        $this->assertTrue($exams->contains('id', $finalExam->id));
        $this->assertTrue($grouped->has($standard->id));

        $payload = json_decode(json_encode($grouped[$standard->id]), true);
        $this->assertSame($finalExam->id, $payload[0]['id']);
        $this->assertSame('End Of Year', $payload[0]['name']);
        $this->assertSame('MATHEMATICS', $payload[0]['subjects']);
    }

    /**
     * @return array{0: School, 1: AcademicYear, 2: Standard, 3: Exam}
     */
    private function seedPromotionExamFixture(): array
    {
        $school = School::create([
            'name' => 'Promotion List School',
            'slug' => 'promo-list-' . uniqid(),
            'email' => 'promo-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 3',
            'status' => 'current',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-01',
        ]);

        $section = Section::create(['school_id' => $school->id, 'name' => 'A', 'status' => 1]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'P6', 'order' => 6, 'status' => 1]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        $finalType = ExamType::create(['name' => 'End Of Year', 'code' => 'FINAL']);
        $midType = ExamType::create(['name' => 'Mid Term', 'code' => 'MID']);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
        ]);

        $finalExam = Exam::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'exam_type_id' => $finalType->id,
            'teacher_id' => $teacher->id,
            'status' => 'undone',
        ]);

        Exam::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'exam_type_id' => $midType->id,
            'teacher_id' => $teacher->id,
            'status' => 'undone',
        ]);

        return [$school, $year, $standard, $finalExam];
    }
}
