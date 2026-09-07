<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaveStudentsClassMatchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StandardLink $s4Link;

    private StandardLink $p1Link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Match School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
            'school_category' => 'o_a_level',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $primary = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $olevel = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'o-level',
            'order' => 2,
            'status' => 1,
        ]);

        $p1 = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);
        $s4 = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Senior Four',
            'status' => 1,
        ]);

        // Intentionally create P.1 first so a silent first-link fallback would be wrong.
        $this->p1Link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $primary->id,
            'section_id' => $p1->id,
            'status' => 1,
        ]);
        $this->s4Link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $olevel->id,
            'section_id' => $s4->id,
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);
    }

    public function test_s_dot_4_resolves_to_senior_four_not_first_class(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveStudents($this->school, $this->year, [
            ['name' => 'S4 Student', 'class' => 'S.4'],
        ]);

        $student = User::where('school_id', $this->school->id)->where('usergroup_id', 6)->first();
        $this->assertNotNull($student);
        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame($this->s4Link->id, $academic->standardLink_id);
        $this->assertSame('Senior Four', $result['created'][0]['class']);
    }

    public function test_senior_4_and_senior_four_aliases_resolve(): void
    {
        $engine = app(OnboardingEngine::class);

        foreach (['Senior 4', 'Senior Four', 'S4', 's 4'] as $alias) {
            $result = $engine->saveStudents($this->school, $this->year, [
                ['name' => "Student {$alias}", 'class' => $alias],
            ]);
            $this->assertSame($this->s4Link->id, $result['created'][0]['standardLink_id'], "Failed for alias {$alias}");
        }
    }

    public function test_unknown_class_throws_instead_of_silent_first_class(): void
    {
        $engine = app(OnboardingEngine::class);

        try {
            $engine->saveStudents($this->school, $this->year, [
                ['name' => 'Lost Student', 'class' => 'Form 4'],
            ]);
            $this->fail('Expected ValidationException for unmatched class');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Form 4', $e->getMessage());
        }

        $this->assertSame(0, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, StudentAcademic::where('school_id', $this->school->id)->count());
    }

    public function test_class_name_candidates_include_short_and_long_forms(): void
    {
        $candidates = OnboardingEngine::classNameCandidates('S.4');
        $lower = array_map('strtolower', $candidates);
        $this->assertContains('s.4', $lower);
        $this->assertContains('senior four', $lower);
        $this->assertContains('senior 4', $lower);
    }
}
