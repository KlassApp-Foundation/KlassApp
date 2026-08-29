<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherSidebarRosterLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sidebar_classes_link_points_to_scoped_roster(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Sidebar School',
            'slug' => 'sidebar-' . uniqid(),
            'email' => 'sidebar-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'sidebar.teacher@t.sch.ug',
        ]);

        $html = view('layouts.teacher.menu')->render();

        $this->assertStringContainsString(url('teacher/classes'), $html);
        $this->assertStringNotContainsString('href="' . url('teacher/standardLinks') . '"', $html);

        $response = $this->actingAs($teacher)->get(route('teacher.classes.index'));
        $response->assertOk();
    }
}
