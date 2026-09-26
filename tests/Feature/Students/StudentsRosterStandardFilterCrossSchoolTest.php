<?php

namespace Tests\Feature\Students;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentsRosterStandardFilterCrossSchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $studentA;
    private User $studentB;
    private StandardLink $streamA;
    private StandardLink $streamB;
    private int $yearAId;
    private int $yearBId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach (['A', 'B'] as $letter) {
            ${'school' . $letter} = School::create([
                'name' => 'Filter School ' . $letter,
                'slug' => 'filter-school-' . strtolower($letter),
                'email' => strtolower($letter) . '@filter.test',
                'phone' => '+25670000000' . ($letter === 'A' ? 1 : 2),
                'status' => 1,
                'registration_country' => 'Uganda',
            ]);
            ${'year' . $letter} = AcademicYear::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => '2026 ' . $letter,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);
            AcademicTerm::create([
                'school_id' => ${'school' . $letter}->id,
                'academic_year_id' => ${'year' . $letter}->id,
                'name' => 'Term 1',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-04-30',
                'status' => 'current',
            ]);
            ${'standard' . $letter} = Standard::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => 'primary',
                'order' => 1,
                'status' => 1,
            ]);
            ${'section' . $letter} = Section::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => 'P.1 ' . $letter,
                'status' => 1,
            ]);
            ${'stream' . $letter} = StandardLink::create([
                'school_id' => ${'school' . $letter}->id,
                'academic_year_id' => ${'year' . $letter}->id,
                'standard_id' => ${'standard' . $letter}->id,
                'section_id' => ${'section' . $letter}->id,
                'stream' => 'A',
                'status' => 1,
            ]);
            ${'admin' . $letter} = User::factory()->create([
                'school_id' => ${'school' . $letter}->id,
                'usergroup_id' => 3,
                'email' => 'admin.' . strtolower($letter) . '@filter.test',
            ]);
            ${'student' . $letter} = User::factory()->create([
                'school_id' => ${'school' . $letter}->id,
                'usergroup_id' => 6,
                'email' => 'student.' . strtolower($letter) . '@filter.test',
            ]);
            Userprofile::create([
                'school_id' => ${'school' . $letter}->id,
                'user_id' => ${'student' . $letter}->id,
                'usergroup_id' => 6,
                'firstname' => $letter === 'A' ? 'Alpha' : 'Beta',
                'lastname' => $letter === 'A' ? 'One' : 'Two',
                'gender' => $letter === 'A' ? 'male' : 'female',
            ]);
            StudentAcademic::create([
                'school_id' => ${'school' . $letter}->id,
                'academic_year_id' => ${'year' . $letter}->id,
                'user_id' => ${'student' . $letter}->id,
                'standardLink_id' => ${'stream' . $letter}->id,
            ]);
        }

        $this->schoolA = $schoolA;
        $this->schoolB = $schoolB;
        $this->yearAId = $yearA->id;
        $this->yearBId = $yearB->id;
        $this->streamA = $streamA;
        $this->streamB = $streamB;
        $this->adminA = $adminA;
        $this->studentA = $studentA;
        $this->studentB = $studentB;
    }

    private function visit(string $query = ''): \Illuminate\Testing\TestResponse
    {
        return $this
            ->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class, \App\Http\Middleware\VerifyCsrfToken::class)
            ->get('/admin/students?status=active' . $query);
    }

    /** @test */
    public function unfiltered_roster_never_shows_school_b_students()
    {
        $this->visit()
            ->assertOk()
            ->assertSee('ALPHA ONE', false)
            ->assertDontSee('BETA', false);
    }

    /** @test */
    public function crafted_foreign_standard_link_id_is_ignored_not_cross_school_applied()
    {
        $this->visit('&standard=' . $this->streamB->id)
            ->assertOk()
            ->assertSee('ALPHA ONE', false)
            ->assertDontSee('BETA', false);
    }

    /** @test */
    public function valid_same_school_standard_link_filters_by_class()
    {
        $this->visit('&standard=' . $this->streamA->id)
            ->assertOk()
            ->assertSee('ALPHA ONE', false)
            ->assertDontSee('BETA', false);
    }

    /** @test */
    public function update_status_refuses_foreign_standard_link()
    {
        $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/admin/standardLink/updateStatus/' . $this->streamB->id, ['status' => 0])
            ->assertRedirect('/admin/standardlinks');

        $this->assertDatabaseHas('standards_link', [
            'id' => $this->streamB->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function update_status_applies_for_same_school_standard_link()
    {
        $this->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/admin/standardLink/updateStatus/' . $this->streamA->id, ['status' => 0])
            ->assertRedirect('/admin/standardlinks');

        $this->assertDatabaseHas('standards_link', [
            'id' => $this->streamA->id,
            'status' => 0,
        ]);
    }
}
