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

        $this->assertSame('KLS-1-1', $first);
        $this->assertSame('KLS-1-2', $second);
        $this->assertSame('KLS-1-3', $third);
    }

    public function test_different_schools_have_independent_sequences()
    {
        $school1a = StudentIdGeneratorService::next(1);
        $school2a = StudentIdGeneratorService::next(2);
        $school1b = StudentIdGeneratorService::next(1);
        $school2b = StudentIdGeneratorService::next(2);

        $this->assertSame('KLS-1-1', $school1a);
        $this->assertSame('KLS-2-1', $school2a);
        $this->assertSame('KLS-1-2', $school1b);
        $this->assertSame('KLS-2-2', $school2b);
    }

    public function test_tight_loop_produces_no_duplicates()
    {
        $ids = [];
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $ids[] = StudentIdGeneratorService::next(1);
        }

        $this->assertSame($iterations, count(array_unique($ids)));
        $this->assertSame('KLS-1-1', $ids[0]);
        $this->assertSame('KLS-1-100', $ids[$iterations - 1]);
    }

    public function test_resumes_from_existing_sequence()
    {
        // Pre-seed the sequence table at a known position
        DB::table('student_id_sequences')->insert([
            'school_id' => 99,
            'next_seq' => 50,
        ]);

        $id = StudentIdGeneratorService::next(99);
        $this->assertSame('KLS-99-50', $id);

        $next = StudentIdGeneratorService::next(99);
        $this->assertSame('KLS-99-51', $next);
    }

    public function test_next_for_student_persists_registration_number()
    {
        $user = \App\Models\User::factory()->create([
            'school_id' => 7,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);

        $id = StudentIdGeneratorService::nextForStudent($user);

        $this->assertSame('KLS-7-1', $id);
        $this->assertSame('KLS-7-1', $user->fresh()->registration_number);
    }

    public function test_school_ids_past_999_do_not_collide_and_a_taken_id_is_skipped(): void
    {
        $legacy = fn (int $school, int $seq) => sprintf('KLS%03d%04d', $school, $seq);
        $this->assertSame('KLS10010001', $legacy(100, 10001));
        $this->assertSame($legacy(100, 10001), $legacy(1001, 1));

        DB::table('student_id_sequences')->insert([
            ['school_id' => 100, 'next_seq' => 10001],
            ['school_id' => 1001, 'next_seq' => 1],
        ]);

        $wide = StudentIdGeneratorService::next(100);
        $other = StudentIdGeneratorService::next(1001);

        $this->assertSame('KLS-100-10001', $wide);
        $this->assertSame('KLS-1001-1', $other);
        $this->assertNotSame($wide, $other);
        $this->assertTrue(StudentIdGeneratorService::isValid('KLS0010001'));
        $this->assertTrue(StudentIdGeneratorService::isValid('KLS00110000'));
        $this->assertTrue(StudentIdGeneratorService::isValid($wide));
        $this->assertTrue(StudentIdGeneratorService::isValid($other));

        \App\Models\User::factory()->create([
            'school_id' => 100,
            'usergroup_id' => 6,
            'registration_number' => 'KLS-100-10002',
        ]);

        $this->assertSame('KLS-100-10003', StudentIdGeneratorService::next(100));
    }
}