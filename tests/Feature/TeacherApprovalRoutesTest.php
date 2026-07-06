<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\MustHaveDesignation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherApprovalRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert(['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schools')->insert(['id' => 1, 'name' => 'Test School', 'slug' => 'test-school', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function makeTeacher(array $designations): User
    {
        return User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => 1,
            'teacher_designations' => $designations,
        ]);
    }

    public function test_middleware_allows_user_with_designation(): void
    {
        $user = $this->makeTeacher(['principal']);
        $request = new Request;
        $request->setUserResolver(fn() => $user);

        $middleware = new MustHaveDesignation;
        $response = $middleware->handle($request, fn($req) => response('ok'), 'principal');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_user_without_designation(): void
    {
        $user = $this->makeTeacher(['leave_applier']);
        $request = new Request;
        $request->setUserResolver(fn() => $user);

        $middleware = new MustHaveDesignation;
        $response = $middleware->handle($request, fn($req) => response('ok'), 'principal');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_blocks_user_with_no_designations(): void
    {
        $user = $this->makeTeacher([]);
        $request = new Request;
        $request->setUserResolver(fn() => $user);

        $middleware = new MustHaveDesignation;
        $response = $middleware->handle($request, fn($req) => response('ok'), 'principal');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_rejects_unauthenticated_request(): void
    {
        $request = new Request;
        $request->setUserResolver(fn() => null);

        $middleware = new MustHaveDesignation;
        $response = $middleware->handle($request, fn($req) => response('ok'), 'principal');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_lesson_plan_approve_route_is_gated_by_designation_principal(): void
    {
        $routes = Route::getRoutes()->getRoutesByMethod()['POST'] ?? [];

        $lessonPlanApprove = collect($routes)->first(
            fn($r) => $r->uri() === 'teacher/lessonplan/approve/{id}'
        );

        $this->assertNotNull($lessonPlanApprove);
        $this->assertStringContainsString(
            'designation:principal',
            json_encode($lessonPlanApprove->gatherMiddleware())
        );
    }

    public function test_leave_approve_route_is_gated_by_designation_leave_checker(): void
    {
        $routes = Route::getRoutes()->getRoutesByMethod()['POST'] ?? [];

        $leaveApprove = collect($routes)->first(
            fn($r) => $r->uri() === 'teacher/leave/approve/{id}'
        );

        $this->assertNotNull($leaveApprove);
        $this->assertStringContainsString(
            'designation:leave_checker',
            json_encode($leaveApprove->gatherMiddleware())
        );
    }

    public function test_assignment_approve_route_is_gated_by_designation_principal(): void
    {
        $routes = Route::getRoutes()->getRoutesByMethod()['POST'] ?? [];

        $assignmentApprove = collect($routes)->first(
            fn($r) => $r->uri() === 'teacher/assignment/approve/{id}'
        );

        $this->assertNotNull($assignmentApprove);
        $this->assertStringContainsString(
            'designation:principal',
            json_encode($assignmentApprove->gatherMiddleware())
        );
    }

    public function test_no_role_middleware_remains_in_any_route(): void
    {
        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $route) {
            $this->assertStringNotContainsString(
                'role:',
                json_encode($route->gatherMiddleware()),
                "Route [{$route->uri()}] still uses deprecated role: middleware"
            );
        }
    }
}
