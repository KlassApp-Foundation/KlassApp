<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteHealthSmokeTest extends TestCase
{
    private function resolveUri(string $uri): ?string
    {
        if (preg_match('/\{[^}]+\}/', $uri)) {
            $uri = preg_replace('/\{id\}/', '1', $uri);
            $uri = preg_replace('/\{transaction\}/', '1', $uri);
            $uri = preg_replace('/\{exam\}/', '1', $uri);
            $uri = preg_replace('/\{student\}/', '1', $uri);
            $uri = preg_replace('/\{userId\}/', '35', $uri);
            $uri = preg_replace('/\{fee\}/', '1', $uri);
            $uri = preg_replace('/\{name\}/', 'test', $uri);
            $uri = preg_replace('/\{slug\}/', 'test-school', $uri);
            $uri = preg_replace('/\{status\}/', 'pending', $uri);
            $uri = preg_replace('/\{standardLink_id\}/', '1', $uri);
            $uri = preg_replace('/\{date\}/', '2026-07-06', $uri);
            $uri = preg_replace('/\{session\}/', 'morning', $uri);

            if (preg_match('/\{[^}]+\}/', $uri)) {
                return null;
            }
        }
        return $uri;
    }

    public function test_no_500_on_admin_get_routes(): void
    {
        $this->runSmokeForPrefix('admin');
    }

    public function test_no_500_on_teacher_get_routes(): void
    {
        $this->runSmokeForPrefix('teacher');
    }

    public function test_no_500_on_accountant_get_routes(): void
    {
        $this->runSmokeForPrefix('accountant');
    }

    public function test_no_500_on_receptionist_get_routes(): void
    {
        $this->runSmokeForPrefix('receptionist');
    }

    public function test_no_500_on_library_get_routes(): void
    {
        $this->runSmokeForPrefix('library');
    }

    public function test_no_500_on_student_get_routes(): void
    {
        $this->runSmokeForPrefix('student');
    }

    public function test_no_role_middleware_registered_in_kernel(): void
    {
        $kernel = file_get_contents(app_path('Http/Kernel.php'));
        $this->assertStringNotContainsString('LaratrustRole', $kernel);
        $this->assertStringNotContainsString("'role'", $kernel);
    }

    private function runSmokeForPrefix(string $prefix): void
    {
        $allRoutes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
        $matched = [];

        foreach ($allRoutes as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, $prefix . '/') || $uri === $prefix) {
                $resolved = $this->resolveUri($uri);
                if ($resolved !== null) {
                    $matched[] = ['uri' => $uri, 'resolved' => $resolved];
                }
            }
        }

        if (empty($matched)) {
            $allUris = [];
            foreach ($allRoutes as $r) {
                $allUris[] = $r->uri();
            }
            $this->fail("No routes found for prefix '{$prefix}'. Sample URIs: " . implode(', ', array_slice($allUris, 0, 10)));
        }

        $failures = [];
        foreach ($matched as $m) {
            $res = $this->get($m['resolved']);
            $status = $res->getStatusCode();

            if ($status >= 500) {
                $failures[] = "{$m['uri']} (resolved: {$m['resolved']}) returned {$status}";
            }
        }

        if (!empty($failures)) {
            $this->fail(
                "Prefix '{$prefix}' has " . count($failures) . " route(s) returning 5xx:\n" .
                implode("\n", $failures)
            );
        }

        $this->addToAssertionCount(count($matched));
    }
}
