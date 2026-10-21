<?php

namespace Tests\Feature\Toshi;

use App\Models\School;
use App\Onboarding\Steps\Steps\StudentsStep;
use App\Onboarding\Steps\Steps\TeachersStep;
use InvalidArgumentException;
use Tests\TestCase;

class ToshiOnboardingV2RosterTextTest extends TestCase
{
    public function test_teacher_chat_text_is_rejected_instead_of_dropped(): void
    {
        $step = app(TeachersStep::class);
        $school = new School;

        try {
            $step->save($school, 'Grace Nabirye, grace@school.test, 0772000000, Primary One, Mathematics');
            $this->fail('A teacher line was accepted as an empty list, so the same question is asked again.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('email', strtolower($e->getMessage()));
        }
    }

    public function test_student_chat_text_is_rejected_instead_of_dropped(): void
    {
        $step = app(StudentsStep::class);
        $school = new School;

        try {
            $step->save($school, 'Amina Okello | Primary One | A | 0772000001');
            $this->fail('A student line was accepted as an empty list, so the same question is asked again.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('class', strtolower($e->getMessage()));
        }
    }

    public function test_skip_and_structured_roster_lists_still_normalize(): void
    {
        $teachers = app(TeachersStep::class);
        $students = app(StudentsStep::class);
        $teacherDraft = [['name' => 'Grace Nabirye', 'email' => 'grace@school.test']];
        $studentDraft = [['name' => 'Amina Okello', 'class' => 'Primary One']];

        $this->assertSame('skip', $teachers->normalize('skip'));
        $this->assertSame('skip', $teachers->normalize('later'));
        $this->assertSame($teacherDraft, $teachers->normalize($teacherDraft));
        $this->assertSame('skip', $students->normalize('none'));
        $this->assertSame($studentDraft, $students->normalize($studentDraft));
    }
}
