<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\RosterScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class RosterScopeServiceCrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $adminB;
    private User $teacherA;
    private int $academicYearAId;
    private int $academicYearBId;
    private Section $sectionA;
    private Section $sectionB;
    private StandardLink $streamA;
    private StandardLink $streamB;
    private RosterScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- School A ---
        $this->schoolA = School::create([
            'name' => 'Test School A', 'slug' => 'test-school-a',
            'email' => 'a@iso.test', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@iso.test',
        ]);

        $this->teacherA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 5,
            'email' => 'teacher.a@iso.test',
        ]);

        $yearA = AcademicYear::create([
            'school_id' => $this->schoolA->id, 'name' => '2026 A',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->academicYearAId = $yearA->id;

        AcademicTerm::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $standardA = Standard::create([
            'school_id' => $this->schoolA->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);

        $this->sectionA = Section::create([
            'school_id' => $this->schoolA->id, 'name' => 'P.1 A', 'status' => 1,
        ]);

        $this->streamA = StandardLink::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'standard_id' => $standardA->id, 'section_id' => $this->sectionA->id,
            'stream' => 'A', 'status' => 1,
        ]);

        Subject::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'standard_id' => $standardA->id, 'section_id' => $this->sectionA->id,
            'name' => 'Math', 'type' => 'core',
        ]);

        $studentA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 6,
            'email' => 'student.a@iso.test',
        ]);
        StudentAcademic::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'user_id' => $studentA->id, 'standardLink_id' => $this->streamA->id,
        ]);

        // --- School B ---
        $this->schoolB = School::create([
            'name' => 'Test School B', 'slug' => 'test-school-b',
            'email' => 'b@iso.test', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 3,
            'email' => 'admin.b@iso.test',
        ]);

        $yearB = AcademicYear::create([
            'school_id' => $this->schoolB->id, 'name' => '2026 B',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $this->academicYearBId = $yearB->id;

        AcademicTerm::create([
            'school_id' => $this->schoolB->id, 'academic_year_id' => $yearB->id,
            'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $standardB = Standard::create([
            'school_id' => $this->schoolB->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);

        $this->sectionB = Section::create([
            'school_id' => $this->schoolB->id, 'name' => 'P.1 B', 'status' => 1,
        ]);

        $this->streamB = StandardLink::create([
            'school_id' => $this->schoolB->id, 'academic_year_id' => $yearB->id,
            'standard_id' => $standardB->id, 'section_id' => $this->sectionB->id,
            'stream' => 'A', 'status' => 1,
        ]);

        $studentB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 6,
            'email' => 'student.b@iso.test',
        ]);
        StudentAcademic::create([
            'school_id' => $this->schoolB->id, 'academic_year_id' => $yearB->id,
            'user_id' => $studentB->id, 'standardLink_id' => $this->streamB->id,
        ]);

        $this->service = app(RosterScopeService::class);
    }

    /** @test */
    public function admin_a_cannot_access_school_b(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You are not authorized for this school.');

        $this->service->visibleSections($this->adminA, $this->schoolB->id, $this->academicYearBId);
    }

    /** @test */
    public function admin_b_cannot_access_school_a(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You are not authorized for this school.');

        $this->service->visibleSections($this->adminB, $this->schoolA->id, $this->academicYearAId);
    }

    /** @test */
    public function teacher_a_cannot_access_school_b(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You are not authorized for this school.');

        $this->service->visibleSections($this->teacherA, $this->schoolB->id, $this->academicYearBId);
    }

    /** @test */
    public function visible_sections_returns_only_school_a_sections(): void
    {
        $sections = $this->service->visibleSections($this->adminA, $this->schoolA->id, $this->academicYearAId)->get();

        $ids = $sections->pluck('id')->toArray();
        $this->assertContains($this->sectionA->id, $ids, 'School A section must be visible');
        $this->assertNotContains($this->sectionB->id, $ids, 'School B section must NOT leak to admin A');
    }

    /** @test */
    public function visible_sections_returns_only_school_b_sections(): void
    {
        $sections = $this->service->visibleSections($this->adminB, $this->schoolB->id, $this->academicYearBId)->get();

        $ids = $sections->pluck('id')->toArray();
        $this->assertContains($this->sectionB->id, $ids, 'School B section must be visible');
        $this->assertNotContains($this->sectionA->id, $ids, 'School A section must NOT leak to admin B');
    }

    /** @test */
    public function students_for_stream_only_returns_school_a_students(): void
    {
        $students = $this->service->studentsForStream($this->streamA, $this->schoolA->id, $this->adminA)->get();

        $schoolIds = $students->pluck('school_id')->unique()->toArray();
        $this->assertCount(1, $schoolIds);
        $this->assertSame($this->schoolA->id, $schoolIds[0]);
    }

    /** @test */
    public function direct_id_substitution_cannot_leak_data(): void
    {
        // Admin A tries to query School B stream by substituting the ID directly
        try {
            $this->service->studentsForStream($this->streamB, $this->schoolA->id, $this->adminA);
            $this->fail('Expected NotFoundHttpException when stream does not belong to school');
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Stream not found', $e->getMessage());
        }
    }

    /** @test */
    public function students_for_stream_returns_only_school_b_students(): void
    {
        $students = $this->service->studentsForStream($this->streamB, $this->schoolB->id, $this->adminB)->get();

        $schoolIds = $students->pluck('school_id')->unique()->toArray();
        $this->assertCount(1, $schoolIds);
        $this->assertSame($this->schoolB->id, $schoolIds[0]);
    }

    /** @test */
    public function school_a_academic_year_rejected_for_school_b(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Academic year not found.');

        $this->service->visibleSections($this->adminA, $this->schoolA->id, $this->academicYearBId);
    }
}
