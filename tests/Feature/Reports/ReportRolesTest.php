<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-157: who can open and change what on the report path, role by role, with the refusals. Admin screens
 * are for school admins and head teachers; the class teacher can print their own class from the teacher
 * screens; every other role, and a teacher of another class, is turned away; another school's class gets
 * the same answer as a class that does not exist. Invented school and people.
 */
class ReportRolesTest extends TestCase
{
    use RefreshDatabase;

    private SyntheticClass $class;

    /** @var array<string, User> */
    private array $actors = [];

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        $this->class = SyntheticClass::build(['learners' => 3, 'counting_sets' => [1]]);
        $this->learner = array_values($this->class->learners)[0];

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actors = [
            'school admin' => $this->member(3),
            'head teacher' => $this->member(4),
            'class teacher' => $this->class->teacher,
            'other teacher' => $this->member(5),
            'student' => $this->member(6),
            'parent' => $this->member(7),
            'accountant' => $this->member(11),
        ];
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function adminScreens(): array
    {
        return [
            'report cards' => ['get', '/admin/reports/cards'],
            'template history' => ['get', '/admin/reports/cards/history'],
            'template preview' => ['get', '/admin/reports/cards/preview/progressive'],
            'learner card preview' => ['get', '/admin/reports/cards/{link}/student/{learner}/preview'],
            'learner card download' => ['get', '/admin/reports/cards/{link}/student/{learner}/download'],
            'progressive rules' => ['get', '/admin/reports/cards/progressive-rules'],
            'school details' => ['get', '/admin/school-identity'],
        ];
    }

    #[DataProvider('adminScreens')]
    public function test_admin_report_screens_open_for_school_admins_and_head_teachers_only(string $verb, string $url): void
    {
        $url = $this->fill($url);
        $allowed = ['school admin', 'head teacher'];

        foreach ($this->actors as $role => $user) {
            $status = $this->actingAs($user)->{$verb}($url)->getStatusCode();
            if (in_array($role, $allowed, true)) {
                $this->assertSame(200, $status, "{$role} opens {$url}");
            } else {
                $this->assertNotSame(200, $status, "{$role} must not open {$url} (got {$status})");
                $this->assertContains($status, [302, 403, 404], "{$role} {$url}");
            }
        }
    }

    public function test_changing_the_template_and_the_report_type_is_for_school_admins_and_head_teachers_only(): void
    {
        foreach ($this->actors as $role => $user) {
            School::query()->whereKey($this->class->school->id)->update(['report_template' => 'warm']);
            $this->actingAs($user)->post('/admin/reports/cards/template', ['report_template' => 'formal']);
            $changed = $this->class->school->fresh()->report_template === 'formal';
            $this->assertSame(in_array($role, ['school admin', 'head teacher'], true), $changed, "{$role} changing the template");
        }
    }

    /** @return array<string, array{0: string}> */
    public static function teacherScreens(): array
    {
        return [
            'class page' => ['/teacher/reports/cards/{link}'],
            'learner card preview' => ['/teacher/reports/cards/{link}/student/{learner}/preview'],
            'learner card download' => ['/teacher/reports/cards/{link}/student/{learner}/download'],
            'whole class pdf' => ['/teacher/reports/cards/{link}/pdf'],
        ];
    }

    #[DataProvider('teacherScreens')]
    public function test_a_class_teacher_prints_their_own_class_and_nobody_else_does(string $url): void
    {
        $url = $this->fill($url);

        $this->assertSame(200, $this->actingAs($this->actors['class teacher'])->get($url)->getStatusCode());
        foreach (['other teacher', 'school admin', 'head teacher', 'student', 'parent', 'accountant'] as $role) {
            $status = $this->actingAs($this->actors[$role])->get($url)->getStatusCode();
            $this->assertNotSame(200, $status, "{$role} must not open {$url} (got {$status})");
        }
    }

    public function test_another_schools_class_is_refused_and_no_card_is_returned(): void
    {
        $foreignAdmin = $this->member(3, $this->otherSchool());

        foreach (['preview', 'download'] as $action) {
            $response = $this->actingAs($foreignAdmin)->get("/admin/reports/cards/{$this->class->link->id}/student/{$this->learner->id}/{$action}");
            $this->assertNotSame(200, $response->getStatusCode(), $action);
            $this->assertNotSame('application/pdf', $response->headers->get('Content-Type'), $action);
        }
    }

    /**
     * KNOWN GAP found by this test: another school's class answers with a redirect while a class that does
     * not exist answers 404, so a person can tell which class ids exist. The report-cards controller is
     * the marks agent's file, so this is reported, not fixed here. Remove the incomplete marker when both
     * answers are the same.
     */
    public function test_another_schools_class_gets_the_same_answer_as_a_class_that_does_not_exist(): void
    {
        $foreignAdmin = $this->member(3, $this->otherSchool());
        $missing = (int) \App\Models\StandardLink::query()->max('id') + 500;

        $real = $this->actingAs($foreignAdmin)->get("/admin/reports/cards/{$this->class->link->id}/student/{$this->learner->id}/preview");
        $none = $this->actingAs($foreignAdmin)->get("/admin/reports/cards/{$missing}/student/{$this->learner->id}/preview");

        $this->assertSame(404, $none->getStatusCode());
        $this->assertSame($none->getStatusCode(), $real->getStatusCode());
        $this->assertSame($none->headers->get('Location'), $real->headers->get('Location'));
    }

    public function test_a_refused_role_changes_nothing_about_the_progressive_rules(): void
    {
        foreach (['student', 'parent', 'accountant', 'other teacher', 'class teacher'] as $role) {
            $response = $this->actingAs($this->actors[$role])->post('/admin/reports/cards/progressive-rules', ['ranking_basis' => 'total', 'use_automatic_division' => '1']);
            $this->assertNotSame(200, $response->getStatusCode(), $role);
        }
        $this->assertNull($this->class->school->fresh()->ranking_basis);
    }

    private function otherSchool(): School
    {
        return School::create([
            'name' => 'Elsewhere School', 'slug' => 'else-'.uniqid(), 'email' => uniqid().'@else.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1,
        ]);
    }

    private function fill(string $url): string
    {
        return str_replace(['{link}', '{learner}'], [$this->class->link->id, $this->learner->id], $url);
    }

    private function member(int $group, ?School $school = null): User
    {
        $school ??= $this->class->school;
        $user = User::factory()->create(['usergroup_id' => $group, 'school_id' => $school->id, 'status' => 'active']);
        Userprofile::create(['user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => $group, 'status' => 'active']);

        return $user;
    }
}
