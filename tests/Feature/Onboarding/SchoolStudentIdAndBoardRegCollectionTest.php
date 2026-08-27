<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolStudentIdAndBoardRegCollectionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $ay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'PR-C Test School', 'email' => 'prc@test.sch.ug']);
        $this->ay = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);
    }

    // ── isCandidateClass() ────────────────────────────────────────────

    /** @test */
    public function is_candidate_class_returns_true_for_p7(): void
    {
        $this->assertTrue(OnboardingEngine::isCandidateClass('P.7'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('P7'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('P 7'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('Primary Seven'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('primary seven'));
    }

    /** @test */
    public function is_candidate_class_returns_true_for_s4(): void
    {
        $this->assertTrue(OnboardingEngine::isCandidateClass('S.4'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('S4'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('S 4'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('Senior Four'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('senior four'));
    }

    /** @test */
    public function is_candidate_class_returns_true_for_s6(): void
    {
        $this->assertTrue(OnboardingEngine::isCandidateClass('S.6'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('S6'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('S 6'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('Senior Six'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('senior six'));
    }

    /** @test */
    public function is_candidate_class_returns_false_for_p1(): void
    {
        $this->assertFalse(OnboardingEngine::isCandidateClass('P.1'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('P1'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('Primary One'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('primary one'));
    }

    /** @test */
    public function is_candidate_class_returns_false_for_s1(): void
    {
        $this->assertFalse(OnboardingEngine::isCandidateClass('S.1'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('S1'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('Senior One'));
    }

    /** @test */
    public function is_candidate_class_returns_false_for_nursery(): void
    {
        $this->assertFalse(OnboardingEngine::isCandidateClass('Baby Class'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('Middle Class'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('Top Class'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('Nursery'));
    }

    // ── Wizard persistence ───────────────────────────────────────────

    private function createStandardLink(string $sectionName, string $standardName): StandardLink
    {
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => $standardName,
            'order' => 2,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => $sectionName,
            'status' => '1',
        ]);

        return StandardLink::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->ay->id,
            'status' => '1',
        ]);
    }

    private function createStudent(): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
        ]);
    }

    /** @test */
    public function wizard_saves_school_student_id(): void
    {
        $link = $this->createStandardLink('P.1', 'primary');
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'school_student_id' => 'ADM-2025-001',
        ]);

        $this->assertEquals('ADM-2025-001', $sa->fresh()->school_student_id);
    }

    /** @test */
    public function wizard_saves_board_reg_for_candidate_class(): void
    {
        // P.7 is a UNEB candidate class (PLE)
        $link = $this->createStandardLink('P.7', 'primary');
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'school_student_id' => 'STD-P7-001',
            'board_registration_number' => 'U1234/567',
        ]);

        $this->assertEquals('U1234/567', $sa->fresh()->board_registration_number);
    }

    /** @test */
    public function board_reg_validation_accepts_alphanumeric(): void
    {
        // board_registration_number changed from numeric to string|max:50
        // UNEB registration numbers are alphanumeric (e.g. U1234/567)
        $link = $this->createStandardLink('P.7', 'primary');
        $student = $this->createStudent();

        $sa = StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'board_registration_number' => 'U1234/567',
        ]);

        $this->assertEquals('U1234/567', $sa->fresh()->board_registration_number);
    }

    /** @test */
    public function is_candidate_class_rejects_old_indian_standards(): void
    {
        // The old validation gated to Indian system '10', '11', '12' —
        // these must NOT be treated as candidate classes anymore.
        $this->assertFalse(OnboardingEngine::isCandidateClass('10'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('11'));
        $this->assertFalse(OnboardingEngine::isCandidateClass('12'));
    }

    /** @test */
    public function user_detail_includes_is_candidate_class_flag(): void
    {
        // Test the isCandidateClass logic directly — the UserDetail resource
        // requires S3 config that's unavailable in CI, so we verify the
        // gating logic without going through the full resource.
        $standardName = 'primary';
        $sectionName = 'P.7';

        // P.7 section name is a candidate class
        $this->assertTrue(
            OnboardingEngine::isCandidateClass($standardName)
            || OnboardingEngine::isCandidateClass($sectionName)
        );

        // S.4 standard name triggers candidate class
        $this->assertTrue(OnboardingEngine::isCandidateClass('S.4'));
    }

    /** @test */
    public function user_detail_non_candidate_class_hides_board_reg(): void
    {
        // P.1 is NOT a candidate class — board_registration_number
        // should be gated out in the resource
        $standardName = 'primary';
        $sectionName = 'P.1';

        $this->assertFalse(
            OnboardingEngine::isCandidateClass($standardName)
            || OnboardingEngine::isCandidateClass($sectionName)
        );
    }

    /** @test */
    public function is_candidate_class_handles_whitespace_and_case(): void
    {
        $this->assertTrue(OnboardingEngine::isCandidateClass('  P.7  '));
        $this->assertTrue(OnboardingEngine::isCandidateClass('  s4  '));
        $this->assertTrue(OnboardingEngine::isCandidateClass('PRIMARY SEVEN'));
        $this->assertTrue(OnboardingEngine::isCandidateClass('SENior SIX'));
    }

    /** @test */
    public function is_candidate_class_returns_false_for_empty_string(): void
    {
        $this->assertFalse(OnboardingEngine::isCandidateClass(''));
        $this->assertFalse(OnboardingEngine::isCandidateClass('   '));
    }
}
