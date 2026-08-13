<?php

namespace Tests\Feature\Grading;

use App\Services\ReportCardCommentService;
use Tests\TestCase;

class ReportCardHeadTeacherCommentTest extends TestCase
{
    private function bank(array $options): array
    {
        return [
            ['min' => 0, 'max' => 199, 'comments' => []],
            ['min' => 200, 'max' => 279, 'comments' => []],
            ['min' => 280, 'max' => 359, 'comments' => []],
            ['min' => 360, 'max' => 439, 'comments' => []],
            ['min' => 440, 'max' => 519, 'comments' => []],
            ['min' => 520, 'max' => 600, 'comments' => $options],
        ];
    }

    /** @test */
    public function head_comment_is_deterministic_and_from_the_bank(): void
    {
        config(['report_card_head_comments.lower' => $this->bank([
            'Excellent.', 'Very good.', 'Keep it up.', 'Well done.', 'Impressive.',
        ])]);

        $svc = new ReportCardCommentService;
        $c1 = $svc->headTeacherCommentFor(580, 'primary_lower', 2649, 21, 'Class comment');
        $c2 = $svc->headTeacherCommentFor(580, 'primary_lower', 2649, 21, 'Class comment');

        $this->assertNotEmpty($c1);
        $this->assertSame($c1, $c2);
        $this->assertContains($c1, ['Excellent.', 'Very good.', 'Keep it up.', 'Well done.', 'Impressive.']);
    }

    /** @test */
    public function head_comment_never_equals_class_comment_when_seed_collides(): void
    {
        $studentId = 100;
        $examId = 7;
        $options = ['Shared phrase', 'Distinct phrase A', 'Distinct phrase B'];
        $seedIndex = crc32("head-{$studentId}-{$examId}") % count($options);

        config(['report_card_head_comments.lower' => $this->bank($options)]);

        $svc = new ReportCardCommentService;
        $head = $svc->headTeacherCommentFor(580, 'primary_lower', $studentId, $examId, $options[$seedIndex]);

        $this->assertNotSame($options[$seedIndex], $head);
        $this->assertContains($head, $options);
    }

    /** @test */
    public function head_comment_advances_to_next_phrase_in_same_tier(): void
    {
        $studentId = 50;
        $examId = 3;
        $options = ['Alpha', 'Beta', 'Gamma'];
        $seedIndex = crc32("head-{$studentId}-{$examId}") % count($options);
        $expected = $options[($seedIndex + 1) % count($options)];

        config(['report_card_head_comments.lower' => $this->bank($options)]);

        $svc = new ReportCardCommentService;
        $head = $svc->headTeacherCommentFor(580, 'primary_lower', $studentId, $examId, $options[$seedIndex]);

        $this->assertSame($expected, $head);
    }

    /** @test */
    public function head_comment_returns_empty_when_every_tier_option_collides(): void
    {
        config(['report_card_head_comments.lower' => $this->bank(['Only phrase'])]);

        $svc = new ReportCardCommentService;
        $head = $svc->headTeacherCommentFor(580, 'primary_lower', 1, 1, 'Only phrase');

        $this->assertSame('', $head);
    }

    /** @test */
    public function head_comment_empty_bank_returns_empty(): void
    {
        config(['report_card_head_comments.lower' => $this->bank([])]);

        $svc = new ReportCardCommentService;
        $this->assertSame('', $svc->headTeacherCommentFor(580, 'primary_lower', 1, 1, 'Anything'));
    }

    /** @test */
    public function head_comment_uses_upper_group_for_primary_standards(): void
    {
        config(['report_card_head_comments.upper' => [
            ['min' => 100, 'max' => 199, 'comments' => []],
            ['min' => 200, 'max' => 239, 'comments' => []],
            ['min' => 240, 'max' => 279, 'comments' => []],
            ['min' => 280, 'max' => 319, 'comments' => []],
            ['min' => 320, 'max' => 359, 'comments' => []],
            ['min' => 360, 'max' => 400, 'comments' => ['Upper band phrase.']],
        ]]);
        config(['report_card_head_comments.lower' => $this->bank(['Lower band phrase.'])]);

        $svc = new ReportCardCommentService;
        $this->assertSame('Upper band phrase.', $svc->headTeacherCommentFor(380, 'primary_upper', 1, 1, 'Class comment'));
    }

    /** @test */
    public function head_comment_band_boundaries_resolve(): void
    {
        config(['report_card_head_comments.lower' => $this->bank(['Boundary phrase.'])]);

        $svc = new ReportCardCommentService;
        $this->assertSame('Boundary phrase.', $svc->headTeacherCommentFor(520, 'primary_lower', 1, 1, 'x'));
        $this->assertSame('Boundary phrase.', $svc->headTeacherCommentFor(600, 'primary_lower', 2, 2, 'x'));
        $this->assertSame('', $svc->headTeacherCommentFor(601, 'primary_lower', 3, 3, 'x'));
    }
}
