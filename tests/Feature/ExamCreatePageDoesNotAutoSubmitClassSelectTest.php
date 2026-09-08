<?php

namespace Tests\Feature;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamCreatePageDoesNotAutoSubmitClassSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_uses_location_assign_class_picker_not_nested_get_form(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Exam Create School',
            'email' => 'exam.create@t.sch.ug',
            'phone' => '0700000089',
            'slug' => 'exam-create-'.uniqid(),
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => AcademicYear::where('school_id', $school->id)->value('id'),
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'email' => 'admin.exam.create@t.sch.ug',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.exams.create'));

        $response->assertOk();
        $response->assertSee('data-create-url', false);
        $response->assertSee('this.dataset.createUrl', false);
        $response->assertDontSee('onchange="this.form.submit()"', false);
        $response->assertSee('type="datetime-local"', false);
        $response->assertSee('href="'.route('admin.exams').'"', false);
        $response->assertSee('Primary Seven', false);
        $response->assertSee((string) $section->id, false);
    }
}
