<?php

namespace Tests\Unit\Services;

use App\Services\StudentIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentIdGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_increasing_ids_for_same_school()
    {
        $first = StudentIdGeneratorService::next(1);
        $second = StudentIdGeneratorService::next(1);
        $third = StudentIdGeneratorService::next(1);

        $this->assertSame('KLS0010001', $first);
        $this->assertSame('KLS0010002', $second);
        $this->assertSame('KLS0010003', $third);
    }

    public function test_different_schools_have_independent_sequences()
    {
        $school1a = StudentIdGeneratorService::next(1);
        $school2a = StudentIdGeneratorService::next(2);
        $school1b = StudentIdGeneratorService::next(1);
        $school2b = StudentIdGeneratorService::next(2);

        $this->assertSame('KLS0010001', $school1a);
        $this->assertSame('KLS0020001', $school2a);
        $this->assertSame('KLS0010002', $school1b);
        $this->assertSame('KLS0020002', $school2b);
    }

    public function test_tight_loop_produces_no_duplicates()
    {
        $ids = [];
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $ids[] = StudentIdGeneratorService::next(1);
        }

        $this->assertSame($iterations, count(array_unique($ids)));
        $this->assertSame('KLS0010001', $ids[0]);
        $this->assertSame('KLS0010100', $ids[$iterations - 1]);
    }

    public function test_resumes_from_existing_sequence()
    {
        // Pre-seed the sequence table at a known position
        DB::table('student_id_sequences')->insert([
            'school_id' => 99,
            'next_seq' => 50,
        ]);

        $id = StudentIdGeneratorService::next(99);
        $this->assertSame('KLS0990050', $id);

        $next = StudentIdGeneratorService::next(99);
        $this->assertSame('KLS0990051', $next);
    }

    public function test_next_for_student_persists_registration_number()
    {
        $user = \App\Models\User::factory()->create([
            'school_id' => 7,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);

        $id = StudentIdGeneratorService::nextForStudent($user);

        $this->assertSame('KLS0070001', $id);
        $this->assertSame('KLS0070001', $user->fresh()->registration_number);
    }

    public function test_school_ids_past_999_do_not_collide_and_a_taken_id_is_skipped(): void
    {
        $small = StudentIdGeneratorService::format(1, 5);
        $wide = StudentIdGeneratorService::format(100, 10001);
        $other = StudentIdGeneratorService::format(1001, 1);

        $this->assertSame('KLS0010005', $small);
        $this->assertSame('KLS00100010001', $wide);
        $this->assertSame('KLS01001000001', $other);
        $this->assertNotSame($small, $wide);
        $this->assertNotSame($small, $other);
        $this->assertNotSame($wide, $other);

        foreach ([$small, $wide, $other] as $id) {
            $this->assertTrue(StudentIdGeneratorService::isValid($id), $id);
            $this->assertSame(1, preg_match(StudentIdGeneratorService::PATTERN, $id));
            $this->assertSame(1, preg_match('/^'.StudentIdGeneratorService::HTML_PATTERN.'$/i', $id));
        }
        $this->assertFalse(StudentIdGeneratorService::isValid('KLS-100-10001'));
        $this->assertFalse(StudentIdGeneratorService::isValid('KLS-1-5'));
        $this->assertSame(0, preg_match('/'.StudentIdGeneratorService::HTML_PATTERN.'/', 'KLS-1-5'));

        $legacy = fn (int $school, int $seq) => sprintf('KLS%03d%04d', $school, $seq);
        $this->assertSame('KLS10010001', $legacy(100, 10001));
        $this->assertSame($legacy(100, 10001), $legacy(1001, 1));
        $this->assertNotSame($wide, $legacy(100, 10001));
        $this->assertNotSame($other, $legacy(1001, 1));

        DB::table('student_id_sequences')->insert([
            ['school_id' => 100, 'next_seq' => 10001],
            ['school_id' => 1001, 'next_seq' => 1],
        ]);

        $this->assertSame($wide, StudentIdGeneratorService::next(100));
        $this->assertSame($other, StudentIdGeneratorService::next(1001));

        \App\Models\User::factory()->create([
            'school_id' => 100,
            'usergroup_id' => 6,
            'registration_number' => StudentIdGeneratorService::format(100, 10002),
        ]);

        $this->assertSame(StudentIdGeneratorService::format(100, 10003), StudentIdGeneratorService::next(100));
    }

    public function test_format_refuses_a_value_that_does_not_fit_in_five_and_six_digits(): void
    {
        foreach ([[100000, 1], [1, 1000000], [100000, 1000000]] as [$school, $seq]) {
            try {
                StudentIdGeneratorService::format($school, $seq);
                $this->fail("school {$school} sequence {$seq} should have been refused");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('5 school digits and 6 sequence digits', $e->getMessage());
            }
        }
    }
}