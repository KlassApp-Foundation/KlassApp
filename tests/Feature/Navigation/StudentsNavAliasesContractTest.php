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
    /** @return list<array<string, mixed>> */
    private function adminRows(): array
    {
        $rows = [];
        foreach (config('navigation.roles.admin.sections') as $section) {
            foreach ($section['rows'] as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function test_students_active_aliases_exclude_people_roster_segments(): void
    {
        $students = collect($this->adminRows())->firstWhere('label', 'Students');
        $this->assertIsArray($students);
        $roster = collect($students['children'] ?? [])->firstWhere('label', 'All students');
        $this->assertIsArray($roster);
        $paths = $roster['paths'] ?? [];

        foreach (['admin/students*', 'admin/student*', 'admin/alumni*', 'admin/blocked_students*'] as $needed) {
            $this->assertContains($needed, $paths);
        }

        $joined = implode(' ', $paths);
        foreach (['parent', 'teacher', 'staff'] as $stolen) {
            $this->assertStringNotContainsString(
                $stolen,
                $joined,
                "All students must not use a path that belongs to another sidebar item [{$stolen}]",
            );
        }

        $teachers = collect($this->adminRows())->firstWhere('label', 'Teachers and staff');
        $parents = collect($this->adminRows())->firstWhere('label', 'Parents');
        $this->assertIsArray($teachers);
        $this->assertIsArray($parents);
        foreach ([$teachers, $parents] as $other) {
            $otherPaths = implode(' ', $other['paths'] ?? []);
            $this->assertStringNotContainsString('student', $otherPaths);
            $this->assertStringNotContainsString('alumni', $otherPaths);
        }
    }

    public static function exclusiveActivePages(): array
    {
        return [
            'students list' => ['admin/students', 'All students'],
            'student add' => ['admin/student/add', 'All students'],
            'teachers list' => ['admin/teachers', 'Teachers and staff'],
            'teacher add' => ['admin/teacher/add', 'Teachers and staff'],
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

        preg_match_all('/<a\s([^>]*)>(.*?)<\/a>/s', $html, $matches, PREG_SET_ORDER);
        $this->assertNotEmpty($matches, "No sidebar links rendered for {$path}");

        $byLabel = [];
        foreach ($matches as $match) {
            $text = trim(html_entity_decode(strip_tags($match[2])));
            if (preg_match('/class="([^"]*)"/', $match[1], $classMatch)) {
                $byLabel[$text] = $classMatch[1];
            }
        }

        $watched = ['Students', 'All students', 'Teachers and staff', 'Parents'];
        foreach ($watched as $label) {
            $this->assertArrayHasKey($label, $byLabel, "Sidebar item [{$label}] missing on {$path}");
        }

        foreach ($watched as $label) {
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
