<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassTeacherStreamSurfaceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Standard $standard;

    private Section $ownedSection;

    private Section $peerSection;

    private User $classTeacher;

    private User $peerTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBeTeacher::class,
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CT Streams School',
            'slug' => 'ct-streams-' . uniqid(),
            'email' => 'ct-streams-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_' . $this->school->id);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $this->classTeacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Owned CT',
            'status' => 'active',
        ]);

        $this->peerTeacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Peer CT',
            'status' => 'active',
        ]);

        $this->ownedSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
            'class_teacher_id' => $this->classTeacher->id,
        ]);

        $this->peerSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Two',
            'status' => 1,
            'class_teacher_id' => $this->peerTeacher->id,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->ownedSection->id,
            'class_teacher_id' => $this->classTeacher->id,
            'status' => '1',
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->peerSection->id,
            'class_teacher_id' => $this->peerTeacher->id,
            'status' => '1',
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->ownedSection->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
    }

    public function test_ct_index_lists_only_owned_sections_and_menu_surface(): void
    {
        $response = $this->actingAs($this->classTeacher)
            ->get(route('teacher.class-stream.index'));

        $response->assertOk();
        $response->assertSee('Primary One');
        $response->assertDontSee('Primary Two');
        $response->assertSee(route('teacher.class-stream.create', $this->ownedSection), false);
        $response->assertSee(route('teacher.class-stream.edit', $this->ownedSection), false);
    }

    public function test_ct_can_add_stream_to_owned_class_and_keeps_base(): void
    {
        $response = $this->actingAs($this->classTeacher)
            ->post(route('teacher.class-stream.store', $this->ownedSection), [
                'stream' => 'A',
            ]);

        $response->assertRedirect(route('teacher.class-stream.index'));
        $response->assertSessionHas('successmessage');

        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary One A',
        ]);

        $stream = Section::query()
            ->where('school_id', $this->school->id)
            ->where('name', 'Primary One A')
            ->first();
        $this->assertNotNull($stream);

        $link = StandardLink::query()
            ->where('school_id', $this->school->id)
            ->where('section_id', $stream->id)
            ->first();
        $this->assertNotNull($link);
        $this->assertNull($link->stream);

        $this->assertSame(
            1,
            Subject::query()
                ->where('school_id', $this->school->id)
                ->where('section_id', $stream->id)
                ->count()
        );
    }

    public function test_ct_cannot_add_or_rename_peer_section(): void
    {
        $this->actingAs($this->classTeacher)
            ->get(route('teacher.class-stream.create', $this->peerSection))
            ->assertForbidden();

        $this->actingAs($this->classTeacher)
            ->post(route('teacher.class-stream.store', $this->peerSection), ['stream' => 'A'])
            ->assertForbidden();

        $this->actingAs($this->classTeacher)
            ->get(route('teacher.class-stream.edit', $this->peerSection))
            ->assertForbidden();

        $this->actingAs($this->classTeacher)
            ->put(route('teacher.class-stream.update', $this->peerSection), ['name' => 'Hacked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary Two A',
        ]);
        $this->assertDatabaseHas('sections', [
            'id' => $this->peerSection->id,
            'name' => 'Primary Two',
        ]);
    }

    public function test_ct_can_rename_owned_stream(): void
    {
        $streamSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One A',
            'status' => 1,
            'class_teacher_id' => $this->classTeacher->id,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $streamSection->id,
            'class_teacher_id' => $this->classTeacher->id,
            'status' => '1',
        ]);

        $response = $this->actingAs($this->classTeacher)
            ->put(route('teacher.class-stream.update', $streamSection), [
                'name' => 'Primary One East',
            ]);

        $response->assertRedirect(route('teacher.class-stream.index'));
        $this->assertDatabaseHas('sections', [
            'id' => $streamSection->id,
            'name' => 'Primary One East',
        ]);
    }

    public function test_subject_teacher_without_ct_assignment_sees_empty_index(): void
    {
        $subjectOnly = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Subject Only',
            'status' => 'active',
        ]);

        $this->actingAs($subjectOnly)
            ->get(route('teacher.class-stream.index'))
            ->assertOk()
            ->assertSee('You are not assigned as class teacher', false);
    }
}
