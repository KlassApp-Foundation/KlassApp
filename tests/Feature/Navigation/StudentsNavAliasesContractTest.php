<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Soft-launch 1d: Students sidebar item must not share active aliases with
 * Teachers / Parents / Staff, so only the current page highlights.
 */
class StudentsNavAliasesContractTest extends TestCase
{
    public function test_students_active_aliases_exclude_people_roster_segments(): void
    {
        $students = collect(config('navigation.roles.admin.groups'))
            ->flatMap(fn (array $group) => $group['items'] ?? [])
            ->firstWhere('label', 'Students');

        $this->assertIsArray($students);
        $active = $students['active'] ?? [];

        $this->assertContains('students', $active);
        $this->assertContains('student', $active);
        $this->assertContains('alumni', $active);
        $this->assertContains('blocked_students', $active);

        foreach (['parents', 'parent', 'teachers', 'teacher', 'staff', 'staffs'] as $stolen) {
            $this->assertNotContains(
                $stolen,
                $active,
                "Students must not use active alias [{$stolen}] (belongs to another sidebar item)",
            );
        }
    }

    public static function exclusiveActivePages(): array
    {
        return [
            'students list' => ['admin/students', 'Students'],
            'student add' => ['admin/student/add', 'Students'],
            'teachers list' => ['admin/teachers', 'Teachers'],
            'teacher add' => ['admin/teacher/add', 'Teachers'],
            'parents list' => ['admin/parents', 'Parents'],
            'parent add' => ['admin/parent/add', 'Parents'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('exclusiveActivePages')]
    public function test_only_matching_sidebar_item_is_active(string $path, string $expectedLabel): void
    {
        $admin = User::factory()->make([
            'usergroup_id' => 3,
            'school_id' => 1,
            'email' => 'nav-aliases@example.test',
        ]);
        $this->actingAs($admin);

        app()->instance('request', Request::create('/'.$path, 'GET'));

        $html = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();

        preg_match_all(
            '/<li[^>]*class="([^"]*dashboard-menu-item[^"]*)"[^>]*>.*?<\/li>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );
        $this->assertNotEmpty($matches, "No dashboard-menu-item rows rendered for {$path}");

        $byLabel = [];
        foreach ($matches as $match) {
            if (preg_match('/>(Students|Teachers|Parents)</', $match[0], $labelMatch)) {
                $byLabel[$labelMatch[1]] = $match[1];
            }
        }

        foreach (['Students', 'Teachers', 'Parents'] as $label) {
            $this->assertArrayHasKey($label, $byLabel, "Sidebar item [{$label}] missing on {$path}");
            $classes = $byLabel[$label];
            $isActive = str_contains($classes, 'dashboard-active')
                || preg_match('/(?:^|\s)active(?:\s|$)/', $classes) === 1;
            if ($label === $expectedLabel) {
                $this->assertTrue($isActive, "Expected [{$label}] active on {$path}; classes={$classes}");
            } else {
                $this->assertFalse($isActive, "Did not expect [{$label}] active on {$path}; classes={$classes}");
            }
        }
    }
}
