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
}