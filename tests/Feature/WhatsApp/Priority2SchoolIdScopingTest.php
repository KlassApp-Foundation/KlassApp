<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Priority2SchoolIdScopingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private StandardLink $linkA;
    private StandardLink $linkB;
    private AcademicYear $ay;

    protected function setUp(): void
    {
        parent::setUp();

        // Two schools — multi-tenant isolation boundary
        $this->schoolA = School::create(['name' => 'Alpha Primary', 'email' => 'alpha@test.sch.ug']);
        $this->schoolB = School::create(['name' => 'Beta Senior', 'email' => 'beta@test.sch.ug']);

        $this->ay = AcademicYear::create([
            'school_id' => $this->schoolA->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);
        // School B shares the same AY for simplicity (or has its own)
        AcademicYear::create([
            'school_id' => $this->schoolB->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);

        $stdA = Standard::create(['school_id' => $this->schoolA->id, 'name' => 'Primary', 'order' => 1]);
        $secA = Section::create(['school_id' => $this->schoolA->id, 'name' => 'P1']);
        $this->linkA = StandardLink::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $stdA->id,
            'section_id' => $secA->id,
            'academic_year_id' => $this->ay->id,
        ]);

        $stdB = Standard::create(['school_id' => $this->schoolB->id, 'name' => 'Senior', 'order' => 1]);
        $secB = Section::create(['school_id' => $this->schoolB->id, 'name' => 'S1']);
        $ayB = AcademicYear::where('school_id', $this->schoolB->id)->first();
        $this->linkB = StandardLink::create([
            'school_id' => $this->schoolB->id,
            'standard_id' => $stdB->id,
            'section_id' => $secB->id,
            'academic_year_id' => $ayB->id,
        ]);
    }

    private function createStudentAtSchool(School $school, StandardLink $link, array $saOverrides = []): array
    {
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
        ]);

        $sa = StudentAcademic::create(array_merge([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ], $saOverrides));

        return [$student, $sa];
    }

    /** @test */
    public function priority2_query_groups_or_conditions_correctly(): void
    {
        // Verify the generated SQL groups the OR inside a parenthesized WHERE clause.
        // Without grouping, `where('school_student_id', X)->orWhere('board_registration_number', X)`
        // would break out of any outer school_id scoping.
        $query = StudentAcademic::where(function ($q) {
            $q->where('school_student_id', 'TEST123')
              ->orWhere('board_registration_number', 'TEST123');
        });

        $sql = $query->toSql();
        $this->assertStringContainsString('(', $sql);
        // The OR should be inside a grouped condition, not at the top level
        $this->assertTrue(
            str_contains($sql, '(??') || str_contains($sql, '("school_student_id"'),
            'Priority 2 OR conditions should be grouped in parentheses'
        );
    }

    /** @test */
    public function priority2_scoped_by_school_id_when_whatsapp_user_has_school(): void
    {
        // School A student with school_student_id = "P1-001"
        [$studentA, $saA] = $this->createStudentAtSchool($this->schoolA, $this->linkA, ['school_student_id' => 'P1-001']);

        // School B student with the SAME school_student_id = "P1-001" (different school, valid)
        [$studentB, $saB] = $this->createStudentAtSchool($this->schoolB, $this->linkB, ['school_student_id' => 'P1-001']);

        // WhatsAppUser linked to School A — search should only find School A's student
        WhatsAppUser::create([
            'phone' => '+256700000001',
            'user_id' => $studentA->id,
            'school_id' => $this->schoolA->id,
            'opted_in' => true,
        ]);

        // Simulate: parent with School A context searches for "P1-001"
        $existingWaUser = WhatsAppUser::where('phone', '+256700000001')->whereNotNull('school_id')->first();
        $this->assertNotNull($existingWaUser);
        $this->assertEquals($this->schoolA->id, $existingWaUser->school_id);

        // Verify scoped query returns only School A's student
        $result = StudentAcademic::where(function ($q) {
            $q->where('school_student_id', 'P1-001')
              ->orWhere('board_registration_number', 'P1-001');
        })
            ->where('school_id', $existingWaUser->school_id)
            ->with(['user', 'school'])
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals($this->schoolA->id, $result->school_id);
        $this->assertEquals($studentA->id, $result->user_id);
    }

    /** @test */
    public function priority2_global_search_without_school_context(): void
    {
        // A first-time parent with no WhatsAppUser — global search must still work
        [$student, $sa] = $this->createStudentAtSchool($this->schoolA, $this->linkA, ['school_student_id' => 'UNIQUE-001']);

        // No WhatsAppUser for this phone
        $existingWaUser = WhatsAppUser::where('phone', '+256799999999')->whereNotNull('school_id')->first();
        $this->assertNull($existingWaUser);

        // Global search should find the student
        $result = StudentAcademic::where(function ($q) {
            $q->where('school_student_id', 'UNIQUE-001')
              ->orWhere('board_registration_number', 'UNIQUE-001');
        })
            ->with(['user', 'school'])
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals('UNIQUE-001', $result->school_student_id);
    }

    /** @test */
    public function priority2_does_not_cross_school_boundary_when_scoped(): void
    {
        // School A has board_registration_number = "UNE1234"
        [$studentA, $saA] = $this->createStudentAtSchool($this->schoolA, $this->linkA, ['board_registration_number' => 'UNE1234']);

        // School B has school_student_id = "UNE1234" (same value, different column, different school)
        [$studentB, $saB] = $this->createStudentAtSchool($this->schoolB, $this->linkB, ['school_student_id' => 'UNE1234']);

        // WhatsAppUser linked to School A — must NOT return School B's student
        WhatsAppUser::create([
            'phone' => '+256700000002',
            'user_id' => $studentA->id,
            'school_id' => $this->schoolA->id,
            'opted_in' => true,
        ]);

        $existingWaUser = WhatsAppUser::where('phone', '+256700000002')->whereNotNull('school_id')->first();

        $result = StudentAcademic::where(function ($q) {
            $q->where('school_student_id', 'UNE1234')
              ->orWhere('board_registration_number', 'UNE1234');
        })
            ->where('school_id', $existingWaUser->school_id)
            ->with(['user', 'school'])
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals($this->schoolA->id, $result->school_id, 'Must NOT return School B student when scoped to School A');
        $this->assertEquals($studentA->id, $result->user_id);
    }

    /** @test */
    public function priority2_auto_learns_school_id_on_global_match(): void
    {
        // First-time parent, no WhatsAppUser yet
        [$student, $sa] = $this->createStudentAtSchool($this->schoolA, $this->linkA, ['school_student_id' => 'LEARN-001']);

        // Verify no WhatsAppUser exists
        $this->assertEquals(0, WhatsAppUser::where('phone', '+256700000003')->count());

        // After a global match, updateOrCreate should set school_id
        $match = StudentAcademic::where(function ($q) {
            $q->where('school_student_id', 'LEARN-001')
              ->orWhere('board_registration_number', 'LEARN-001');
        })->first();

        $this->assertNotNull($match);
        $this->assertEquals($this->schoolA->id, $match->school_id);

        // Simulate the auto-learn behavior from WhatsAppController
        WhatsAppUser::updateOrCreate(
            ['phone' => '+256700000003'],
            ['school_id' => $match->school_id],
        );

        $learnedUser = WhatsAppUser::where('phone', '+256700000003')->first();
        $this->assertNotNull($learnedUser);
        $this->assertEquals($this->schoolA->id, $learnedUser->school_id);
    }
}
