<?php

namespace Tests\Feature\Students;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeedStudentIdSequencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_eleven_digit_id_seeds_the_six_digit_sequence(): void
    {
        DB::table('usergroups')->insert([
            'id' => 6,
            'name' => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'name' => 'Sequence School',
            'email' => 'sequence-'.Str::random(6).'@example.test',
            'phone' => '+256700000001',
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Sequence Learner',
            'email' => 'learner-'.Str::random(6).'@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // School 1, sequence 10001. The old read (from the 7th character)
        // turns this into 1010001 and would seed 1010002.
        DB::table('student_academics')->insert([
            'school_id' => $school->id,
            'academic_year_id' => 1,
            'user_id' => $student->id,
            'klassapp_student_id' => 'KLS00001010001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('students:seed-sequences', ['--dry-run' => true])
            ->expectsOutputToContain("school_id={$school->id}: next_seq would be 10002")
            ->assertSuccessful();
    }
}
