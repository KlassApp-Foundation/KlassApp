<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaveExamsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private Section $section;

    private Subject $subject;

    private AcademicTerm $term;

    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Exam School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'o-level',
            'order' => 1,
            'status' => 1,
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Senior Four',
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => '456',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $this->examType = ExamType::create([
            'name' => 'Mid-Term',
            'code' => 'MID',
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Exam Admin',
            'email' => 'admin.'.Str::random(6).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_save_exams_persists_exam_row(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveExams($this->school, $this->year, [
            [
                'type' => 'Mid-Term',
                'term' => 'Term 1',
                'class' => 'S.4',
                'subject' => 'Mathematics',
            ],
        ], $this->admin->id);

        $this->assertCount(1, $result['created']);
        $exam = Exam::where('school_id', $this->school->id)->first();
        $this->assertNotNull($exam);
        $this->assertSame($this->section->id, $exam->section_id);
        $this->assertSame($this->subject->id, $exam->subject_id);
        $this->assertSame($this->term->id, $exam->academic_term_id);
        $this->assertSame($this->examType->id, $exam->exam_type_id);
        $this->assertSame($this->admin->id, $exam->teacher_id);
    }

    public function test_save_exams_requires_matching_class(): void
    {
        $engine = app(OnboardingEngine::class);

        $this->expectException(ValidationException::class);
        $engine->saveExams($this->school, $this->year, [
            [
                'type' => 'Mid-Term',
                'class' => 'Does Not Exist',
                'subject' => 'Mathematics',
            ],
        ], $this->admin->id);
    }

    public function test_save_exams_requires_class(): void
    {
        $engine = app(OnboardingEngine::class);

        $this->expectException(ValidationException::class);
        $engine->saveExams($this->school, $this->year, [
            [
                'type' => 'Mid-Term',
                'class' => '',
            ],
        ], $this->admin->id);
    }
}
