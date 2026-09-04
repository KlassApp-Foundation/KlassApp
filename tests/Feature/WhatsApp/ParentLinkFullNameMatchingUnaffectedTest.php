<?php

namespace Tests\Feature\WhatsApp;

use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Confirms FullName / displayName accessor cleanup does not break parent-link
 * identification matching — candidates are matched on users.name LIKE, not FullName.
 */
class ParentLinkFullNameMatchingUnaffectedTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_candidate_students_matches_raw_name_column_with_digit_suffix(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'FullName Match School',
            'email' => 'fnmatch@test.sch.ug',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => Standard::create(['school_id' => $school->id, 'name' => 'P3', 'order' => 3])->id,
            'section_id' => Section::create(['school_id' => $school->id, 'name' => 'P.3'])->id,
            'academic_year_id' => DB::table('academic_years')->insertGetId([
                'school_id' => $school->id,
                'name' => '2026',
                'description' => 'Current Academic Year',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);

        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'email' => 'mary.polite.'.uniqid().'@test.sch.ug',
            'password' => bcrypt('password'),
            // Digit-suffixed raw login/storage name (pre-displayName cleanup era).
            'name' => 'mary polite33453',
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $student->id,
            'usergroup_id' => 6,
            'firstname' => 'Mary',
            'lastname' => 'Polite',
        ]);

        // UserprofileObserver rewrites users.name on create — restore a digit-suffixed
        // roster name so we exercise the real matching path parents hit in production.
        DB::table('users')->where('id', $student->id)->update(['name' => 'mary polite33453']);
        $student->refresh();

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'roll_number' => '1',
        ]);

        // Display/FullName strip the suffix (profile names uppercased by accessors).
        $this->assertSame('MARY POLITE', $student->fresh()->displayName);
        $this->assertStringContainsString('33453', $student->fresh()->name);

        $candidates = app(ParentLinkRequestService::class)
            ->findCandidateStudents('mary polite', 'P.3', $school->id);

        $this->assertTrue(
            $candidates->contains(fn (User $u) => (int) $u->id === (int) $student->id),
            'LIKE match on users.name must still find digit-suffixed rows when parent types a clean name'
        );
    }
}
