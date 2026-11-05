<?php

namespace Tests\Feature\Integration;

use App\Services\Marks\ExamMarkWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/**
 * requiresCorrection() must answer from the closed-mark rule (writesAreRefused), not return false.
 * Branches #1280 and #1281 still carry the older `return false` copy; this pins the merged behaviour.
 */
class MarkWriterRequiresCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
    }

    public function test_open_marks_need_no_correction_reason(): void
    {
        $this->assertFalse(app(ExamMarkWriter::class)->requiresCorrection($this->exams['English']));
    }

    public function test_submitted_approved_and_locked_marks_need_a_correction_reason(): void
    {
        $writer = app(ExamMarkWriter::class);

        $this->submission('English', 'submitted', 'pending');
        $this->assertTrue($writer->requiresCorrection($this->exams['English']->fresh()));

        $this->submission('Mathematics', 'locked', 'approved');
        $this->assertTrue($writer->requiresCorrection($this->exams['Mathematics']->fresh()));
    }

    public function test_reopened_marks_are_open_again(): void
    {
        $this->submission('English', 'reopened', 'pending');
        $this->assertFalse(app(ExamMarkWriter::class)->requiresCorrection($this->exams['English']->fresh()));
    }
}
