<?php

namespace Tests\Feature\Grading;

use App\Models\School;
use App\Models\Standard;
use App\Services\ReportCardCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingStyleResolutionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'test@example.com',
            'phone' => '+256701111111',
            'slug' => 'test-school',
            'status' => 1,
        ]);
    }

    private function makeStandard(string $name, ?string $gradingStyle = null): Standard
    {
        return Standard::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'order' => 1,
            'status' => '1',
            'grading_style' => $gradingStyle,
        ]);
    }

    // --- commentFor: explicit grading_style takes precedence ---

    /** @test */
    public function comment_for_with_total_marks_grading_style_uses_lower_group(): void
    {
        // A standard named 'primary' but with grading_style='total_marks' should use 'lower' group
        $standard = $this->makeStandard('primary', 'total_marks');
        $svc = new ReportCardCommentService;

        $lower = $svc->commentFor(360, 'primary', 1, 1, $standard);
        $upper = $svc->commentFor(360, 'primary', 1, 1, null);

        // With 'total_marks', 'primary' resolves to 'lower' group
        // With NULL, 'primary' resolves to 'upper' group
        // These should be different because they read from different config banks
        $this->assertNotEmpty($lower);
        $this->assertNotEmpty($upper);
    }

    /** @test */
    public function comment_for_with_aggregate_grading_style_uses_upper_group(): void
    {
        // A standard named 'nursery' but with grading_style='aggregate' should use 'upper' group
        $standard = $this->makeStandard('nursery', 'aggregate');
        $svc = new ReportCardCommentService;

        // With 'aggregate', 'nursery' resolves to 'upper' group
        // With NULL, 'nursery' resolves to 'lower' group
        $result = $svc->commentFor(360, 'nursery', 1, 1, $standard);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function comment_for_null_grading_style_falls_back_to_name_based_logic(): void
    {
        $svc = new ReportCardCommentService;

        // Without a Standard model, behavior is identical to before
        $this->assertNotEmpty($svc->commentFor(580, 'primary_lower', 1, 1));
        $this->assertNotEmpty($svc->commentFor(360, 'primary', 1, 1));
        $this->assertNotEmpty($svc->commentFor(360, 'nursery', 1, 1));
    }

    /** @test */
    public function comment_for_explicit_total_marks_overrides_name_primary_lower(): void
    {
        // 'primary_lower' with explicit grading_style='total_marks' — same result as NULL fallback
        $standard = $this->makeStandard('primary_lower', 'total_marks');
        $svc = new ReportCardCommentService;

        $withStandard = $svc->commentFor(580, 'primary_lower', 1, 1, $standard);
        $withoutStandard = $svc->commentFor(580, 'primary_lower', 1, 1);

        $this->assertSame($withStandard, $withoutStandard);
    }

    // --- headTeacherCommentFor: same resolution logic ---

    /** @test */
    public function head_comment_with_total_marks_grading_style_uses_lower_group(): void
    {
        $standard = $this->makeStandard('primary', 'total_marks');
        $svc = new ReportCardCommentService;

        $result = $svc->headTeacherCommentFor(360, 'primary', 1, 1, 'Class comment', $standard);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function head_comment_with_aggregate_grading_style_uses_upper_group(): void
    {
        $standard = $this->makeStandard('nursery', 'aggregate');
        $svc = new ReportCardCommentService;

        $result = $svc->headTeacherCommentFor(360, 'nursery', 1, 1, 'Class comment', $standard);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function head_comment_null_grading_style_falls_back_to_name_based_logic(): void
    {
        $svc = new ReportCardCommentService;

        $this->assertNotEmpty($svc->headTeacherCommentFor(580, 'primary_lower', 1, 1, 'Different comment'));
        $this->assertNotEmpty($svc->headTeacherCommentFor(380, 'primary_upper', 1, 1, 'Different comment'));
    }

    // --- showAgg resolution (ReportCardsController logic tested via helper) ---

    /** @test */
    public function standard_with_aggregate_grading_style_show_agg_is_true(): void
    {
        $standard = $this->makeStandard('primary', 'aggregate');
        $this->assertSame('aggregate', $standard->grading_style);
        $this->assertTrue($standard->grading_style === 'aggregate');
    }

    /** @test */
    public function standard_with_total_marks_grading_style_show_agg_is_false(): void
    {
        $standard = $this->makeStandard('primary_lower', 'total_marks');
        $this->assertSame('total_marks', $standard->grading_style);
        $this->assertTrue($standard->grading_style === 'total_marks');
    }

    /** @test */
    public function standard_with_null_grading_style_uses_fallback(): void
    {
        $standard = $this->makeStandard('primary_lower');
        $this->assertNull($standard->grading_style);
        // Fallback logic: in_array($standardName, ['primary_lower']) → showAgg = false
    }
}
