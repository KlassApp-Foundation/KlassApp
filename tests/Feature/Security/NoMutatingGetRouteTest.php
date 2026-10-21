<?php

namespace Tests\Feature\Security;

use App\Helpers\SiteHelper;
use App\Models\Authentication;
use App\Models\LeaveType;
use App\Models\Standard;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * GET and HEAD must not delete or change records. Each converted route is
 * checked: the old verb does nothing, the new verb requires a CSRF token on
 * web routes (or a Sanctum bearer token on API routes), an authorized user
 * is accepted, and another role is refused.
 */
class NoMutatingGetRouteTest extends TestCase
{
    use RefreshDatabase;

    private const MUTATING_METHODS = [
        'destroy',
        'delete',
        'forceDestroy',
        'destroyImmunization',
        'destroyIncident',
        'resetPassword',
        'emailVerification',
        'eventapprove',
        'resendCode',
        'impersonate',
        'librarianimpersonate',
        'studentimpersonate',
        'schoolAdminimpersonate',
        'stopImpersonate',
        'updatetoken',
        'confirm',
        'approveStore',
        'rejectStore',
        'updateStatus',
        'logout',
    ];

    /** @var array<string, User> */
    private array $users = [];

    private int $schoolId;

    private string $resetEmail = 'reset-target@no-get-mutations.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'No Get Mutations School',
            'slug' => 'no-get-mutations',
            'email' => 'no-get-mutations@test.sch.ug',
            'phone' => '+256700000333',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Standard::create([
            'school_id' => $this->schoolId,
            'name' => 'Primary 1',
            'order' => 1,
            'status' => 1,
        ]);

        DB::table('school_details')->insert([
            'school_id' => $this->schoolId,
            'meta_key' => SiteHelper::TEACHER_RECEPTIONIST_ACCESS_KEY,
            'meta_value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([1 => 'super', 3 => 'admin', 4 => 'subadmin', 5 => 'teacher', 6 => 'student', 8 => 'librarian', 10 => 'receptionist', 11 => 'accountant'] as $group => $key) {
            $this->users[$key] = User::factory()->create([
                'school_id' => $this->schoolId,
                'usergroup_id' => $group,
                'email' => $key.'@no-get-mutations.test',
                'status' => 'active',
                'email_verified' => 1,
            ]);
        }

        $this->users['reset'] = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => $this->resetEmail,
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_no_get_or_head_route_maps_to_a_mutating_action(): void
    {
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            $methods = array_map('strtoupper', $route->methods());
            if (! array_intersect($methods, ['GET', 'HEAD'])) {
                continue;
            }

            $action = $route->getActionName();
            $uri = $route->uri();
            $method = $this->actionMethod($action);

            if (in_array($method, self::MUTATING_METHODS, true) || $this->uriIsMutating($uri)) {
                $offenders[] = implode('|', $methods).' '.$uri.' '.$action;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "GET/HEAD routes still map to a mutating action:\n".implode("\n", $offenders)
        );
    }

    #[DataProvider('convertedRoutes')]
    public function test_converted_route_rejects_get_and_accepts_the_write_verb(array $case): void
    {
        $url = $this->url($case['uri']);
        $verb = $case['verb'];

        $get = $this->request(null, 'get', $url, false);
        $this->assertSame(405, $get->status(), "GET {$url} must not run the action. ".$this->explain($get));

        if ($case['gate'] === 'api') {
            $anonymous = $this->request(null, $verb === 'DELETE' ? 'deleteJson' : 'postJson', $url, false);
            $this->assertSame(401, $anonymous->status(), "Unauthenticated {$verb} {$url} must be refused. ".$this->explain($anonymous));

            // API routes use the api middleware group, which has no CSRF middleware.
            // Sanctum accepts the session guard or a bearer token. A credential-less
            // call is 401, and GET is 405, so a cross-site link cannot mutate.
            $ok = $this->request($this->users[$case['actor']], $verb === 'DELETE' ? 'deleteJson' : 'postJson', $url, false, 'sanctum');
            $this->assertTrue($this->accepted($ok), "Sanctum {$verb} {$url} was not accepted. ".$this->explain($ok));

            return;
        }

        $actor = $case['actor'] === null ? null : $this->users[$case['actor']];
        $missing = $this->request($actor, strtolower($verb), $url, false);
        $this->assertSame(419, $missing->status(), "{$verb} {$url} without a CSRF token must be rejected. ".$this->explain($missing));

        $ok = $this->request($actor, strtolower($verb), $url, true);
        $this->assertTrue($this->accepted($ok), "Authorized {$verb} {$url} was not accepted. ".$this->explain($ok));

        if ($case['intruder'] !== null) {
            $intruder = $this->request($this->users[$case['intruder']], strtolower($verb), $url, true);
            $this->assertTrue(
                $this->refused($intruder),
                "{$case['intruder']} must not {$verb} {$url}. ".$this->explain($intruder).' location '.$intruder->headers->get('Location')
            );
        }
    }

    public function test_email_verification_link_does_not_verify_until_post(): void
    {
        $user = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'verify-me@no-get-mutations.test',
            'email_verified' => 0,
            'email_verified_at' => null,
            'email_verification_code' => 'verify-token-no-get',
            'status' => 'active',
        ]);

        $this->get('/emailverification/verify-token-no-get')
            ->assertOk()
            ->assertSee('Confirm your email')
            ->assertSee('Verify email');

        $this->assertSame(0, (int) $user->fresh()->email_verified);

        $this->request(null, 'post', '/emailverification/verify-token-no-get', false)->assertStatus(419);
        $this->assertSame(0, (int) $user->fresh()->email_verified);

        $this->request(null, 'post', '/emailverification/verify-token-no-get', true)->assertRedirect();
        $fresh = $user->fresh();
        $this->assertSame(1, (int) $fresh->email_verified);
        $this->assertNotNull($fresh->email_verified_at);

        $this->get('/emailverification/not-a-real-token')->assertRedirect('/login');
    }

    public function test_dismiss_onboarding_get_does_not_write_a_preference(): void
    {
        $admin = $this->users['admin'];
        $key = UserPreference::setupBannerDismissedKey($this->schoolId);

        $this->actingAs($admin)->get('/dismiss-onboarding')->assertStatus(405);
        $this->assertNull(UserPreference::get($admin, $key));

        $this->request($admin, 'post', '/dismiss-onboarding', false)->assertStatus(419);
        $this->assertNull(UserPreference::get($admin, $key));

        $this->request(null, 'post', '/dismiss-onboarding', true)->assertRedirect('/login');
        $this->assertNull(UserPreference::get($admin, $key));

        $this->request($admin, 'post', '/dismiss-onboarding', true)->assertRedirect();
        $this->assertNotNull(UserPreference::get($admin, $key));
    }

    public function test_password_reset_resend_get_does_not_issue_a_code(): void
    {
        $this->get('/password/reset-code/resend?email='.urlencode($this->resetEmail))->assertStatus(405);
        $this->assertSame(0, Authentication::where('type', 'password_reset')->count());

        $this->request(null, 'post', '/password/reset-code/resend', false)->assertStatus(419);
        $this->assertSame(0, Authentication::where('type', 'password_reset')->count());

        $this->request($this->users['student'], 'post', '/password/reset-code/resend', true)->assertRedirect();
        $this->assertSame(0, Authentication::where('type', 'password_reset')->count());

        $this->request(null, 'post', '/password/reset-code/resend', true)->assertRedirect();
        $this->assertSame(1, Authentication::where('user_id', $this->users['reset']->id)->where('type', 'password_reset')->where('status', 0)->count());
    }

    public function test_leave_type_delete_get_leaves_the_row_and_delete_removes_it(): void
    {
        $yearId = DB::table('academic_years')->where('school_id', $this->schoolId)->value('id');
        $leaveType = LeaveType::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $yearId,
            'name' => 'Sick',
            'max_no_of_days' => 5,
            'status' => 1,
        ]);
        $url = '/admin/leavetype/delete/'.$leaveType->id;

        $this->actingAs($this->users['admin'])->get($url)->assertStatus(405);
        $this->assertNull($leaveType->fresh()->deleted_at);

        $this->request($this->users['admin'], 'delete', $url, false)->assertStatus(419);
        $this->assertNull($leaveType->fresh()->deleted_at);

        $this->request($this->users['teacher'], 'delete', $url, true)->assertRedirect('/teacher/dashboard');
        $this->assertNull($leaveType->fresh()->deleted_at);

        $this->request($this->users['admin'], 'delete', $url, true);
        $this->assertNotNull($leaveType->fresh()->deleted_at);
    }

    public static function convertedRoutes(): array
    {
        $cases = [];
        foreach (self::routes() as $route) {
            $cases[$route['verb'].' '.$route['uri']] = [$route];
        }

        return $cases;
    }

    /**
     * @return list<array{verb: string, uri: string, gate: string, actor: ?string, intruder: ?string}>
     */
    private static function routes(): array
    {
        $admin = static fn (string $uri, string $verb = 'DELETE'): array => [
            'verb' => $verb, 'uri' => $uri, 'gate' => 'web', 'actor' => 'admin', 'intruder' => 'teacher',
        ];
        $teacher = static fn (string $uri): array => [
            'verb' => 'DELETE', 'uri' => $uri, 'gate' => 'web', 'actor' => 'teacher', 'intruder' => 'admin',
        ];
        $student = static fn (string $uri): array => [
            'verb' => 'DELETE', 'uri' => $uri, 'gate' => 'web', 'actor' => 'student', 'intruder' => 'teacher',
        ];
        $api = static fn (string $uri, string $verb = 'DELETE'): array => [
            'verb' => $verb, 'uri' => $uri, 'gate' => 'api', 'actor' => 'teacher', 'intruder' => null,
        ];

        return [
            $admin('accountant/task/{id}/delete') + ['actor' => 'accountant', 'intruder' => 'teacher'],
            $admin('admin/academic/delete/{id}') + ['intruder' => 'subadmin'],
            $admin('admin/admission/delete/{id}'),
            $admin('admin/holiday/delete/{id}'),
            $admin('admin/standardLink/delete/{id}'),
            $admin('admin/notes/delete/{id}'),
            $admin('admin/discipline/delete/{id}'),
            $admin('admin/phonenumber/delete/{id}'),
            $admin('admin/document/delete/{id}'),
            $admin('admin/events/delete/{id}'),
            $admin('admin/event/approve/{id}', 'POST'),
            $admin('admin/homework/delete/{id}'),
            $admin('admin/notice/delete/{id}'),
            $admin('admin/leavetype/delete/{id}'),
            $admin('admin/magazine/delete/{id}'),
            $admin('admin/task/{id}/delete'),
            $admin('admin/visitorlog/delete/{id}'),
            $admin('admin/calllog/delete/{id}'),
            $admin('admin/postalrecord/delete/{id}'),
            $admin('admin/classwall/page/delete/{id}'),
            $admin('admin/classwall/post/delete/{id}'),
            $admin('admin/classwall/post/delete/comment/{comment_id}'),
            $admin('admin/classwall/post/reply/delete/comment/{post_comment_id}'),
            $admin('admin/user/resetPassword/{id}', 'POST'),
            $admin('admin/user/{id}/verificationcode', 'POST'),
            ['verb' => 'DELETE', 'uri' => 'library/bookscategory/delete/{id}', 'gate' => 'web', 'actor' => 'librarian', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'library/books/delete/{id}', 'gate' => 'web', 'actor' => 'librarian', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'library/booklending/delete/{id}', 'gate' => 'web', 'actor' => 'librarian', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'library/task/{id}/delete', 'gate' => 'web', 'actor' => 'librarian', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'receptionist/visitorlog/delete/{id}', 'gate' => 'web', 'actor' => 'receptionist', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'receptionist/calllog/delete/{id}', 'gate' => 'web', 'actor' => 'receptionist', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'receptionist/postalrecord/delete/{id}', 'gate' => 'web', 'actor' => 'receptionist', 'intruder' => 'teacher'],
            ['verb' => 'DELETE', 'uri' => 'receptionist/task/{id}/delete', 'gate' => 'web', 'actor' => 'receptionist', 'intruder' => 'teacher'],
            $student('student/task/{id}/delete'),
            $student('student/assignment/delete/{id}'),
            $student('student/homework/delete/{id}'),
            $student('student/classwall/post/delete/comment/{comment_id}'),
            $student('student/classwall/post/reply/delete/comment/{post_comment_id}'),
            $teacher('teacher/assignment/delete/{id}'),
            $teacher('teacher/leave/delete/{id}'),
            $teacher('teacher/task/{id}/delete'),
            $teacher('teacher/lessonplan/delete/{id}'),
            $teacher('teacher/homework/delete/{id}'),
            $teacher('teacher/visitorlog/delete/{id}'),
            $teacher('teacher/calllog/delete/{id}'),
            $teacher('teacher/postalrecord/delete/{id}'),
            $teacher('teacher/classwall/post/delete/{id}'),
            $teacher('teacher/classwall/post/delete/comment/{comment_id}'),
            $teacher('teacher/classwall/post/reply/delete/comment/{post_comment_id}'),
            ['verb' => 'POST', 'uri' => 'teacher/{id}/impersonate', 'gate' => 'web', 'actor' => 'admin', 'intruder' => 'teacher'],
            ['verb' => 'POST', 'uri' => 'library/{id}/impersonate', 'gate' => 'web', 'actor' => 'admin', 'intruder' => 'teacher'],
            ['verb' => 'POST', 'uri' => 'student/{id}/impersonate', 'gate' => 'web', 'actor' => 'admin', 'intruder' => 'teacher'],
            ['verb' => 'POST', 'uri' => 'schooladmin/{id}/impersonate', 'gate' => 'web', 'actor' => 'super', 'intruder' => 'admin'],
            ['verb' => 'POST', 'uri' => 'teacher/impersonate/stop', 'gate' => 'web', 'actor' => null, 'intruder' => null],
            ['verb' => 'POST', 'uri' => 'password/reset-code/resend', 'gate' => 'web', 'actor' => null, 'intruder' => 'student'],
            ['verb' => 'POST', 'uri' => 'dismiss-onboarding', 'gate' => 'web', 'actor' => 'admin', 'intruder' => null],
            $api('api/v2/leave/delete/{id}'),
            $api('api/v2/homework/delete/{id}/{student_id}'),
            $api('api/v2/assignment/delete/{id}/{student_id}'),
            $api('api/v2/task/delete/{id}'),
            $api('api/v2/updatetoken', 'POST'),
            $api('api/teacher/assignment/delete/{id}'),
            $api('api/teacher/homework/delete/{id}'),
            $api('api/teacher/leave/delete/{id}'),
            $api('api/teacher/task/delete/{id}'),
            $api('api/teacher/performance/delete/{performance_id}'),
        ];
    }

    private function url(string $uri): string
    {
        $uri = str_replace('{student_id}', (string) $this->users['student']->id, $uri);

        return '/'.preg_replace('/\{[^}]+\}/', '999999', $uri);
    }

    private function request(?User $user, string $method, string $url, bool $csrf, string $guard = 'web'): TestResponse
    {
        $this->app['env'] = 'production';

        try {
            auth()->guard('web')->logout();
            auth()->forgetGuards();
            $this->flushSession();

            $pending = $user ? $this->actingAs($user, $guard) : $this;
            $pending = $pending->withSession(['_token' => 'csrf-test-token']);
            if ($csrf) {
                $pending = $pending->withHeader('X-CSRF-TOKEN', 'csrf-test-token');
            }

            $payload = [
                'email' => $this->resetEmail,
                'device_name' => 'phpunit-device',
            ];

            return $pending->{$method}($url, $payload);
        } finally {
            $this->app['env'] = 'testing';
            $this->repairTransactions();
        }
    }

    /**
     * A legacy destroy that throws can leave SQLite's PDO transaction flag set
     * after Laravel's counter is cleared (PHP 8.4 BEGIN). Put the test
     * transaction back before the next request or the next test.
     */
    private function repairTransactions(): void
    {
        $connection = DB::connection();

        try {
            while ($connection->transactionLevel() > 1) {
                $connection->rollBack();
            }
        } catch (\Throwable) {
            // The counter and the PDO flag can disagree; the check below repairs that.
        }

        $pdo = $connection->getPdo();
        if ($pdo && $connection->transactionLevel() === 0 && $pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
    }

    /**
     * The write verb reached an authorized controller. A missing row still
     * throws inside some legacy destroy methods; that is not a routing refusal.
     */
    private function accepted(TestResponse $response): bool
    {
        if (in_array($response->status(), [405, 419], true)) {
            return false;
        }

        // Some API destroy methods answer 401 when the id does not exist.
        // The auth middleware's body is "Unauthenticated.", so that still fails.
        if ($response->status() === 401) {
            $body = $response->getContent();

            return str_contains($body, '"success":false') && str_contains($body, 'Unauthorized');
        }

        return true;
    }

    private function refused(TestResponse $response): bool
    {
        if (in_array($response->status(), [401, 403, 404], true)) {
            return true;
        }

        if ($response->status() !== 302) {
            return false;
        }

        $location = (string) $response->headers->get('Location');

        return str_contains($location, '/dashboard')
            || str_contains($location, '/login')
            || str_contains($location, '/home');
    }

    private function explain(TestResponse $response): string
    {
        $exception = $response->exception;
        $detail = $exception ? $exception::class.': '.$exception->getMessage() : substr($response->getContent(), 0, 240);

        return 'status '.$response->status().' '.$detail;
    }

    private function actionMethod(string $action): string
    {
        if (! str_contains($action, '@')) {
            return '';
        }

        return substr($action, strrpos($action, '@') + 1);
    }

    private function uriIsMutating(string $uri): bool
    {
        return (bool) preg_match(
            '#(?:^|/)(delete|remove)(?:/|$)|resetPassword|verificationcode|dismiss-onboarding|(?:^|/)impersonate(?:/|$)|updatetoken|event/approve#',
            $uri
        );
    }
}
