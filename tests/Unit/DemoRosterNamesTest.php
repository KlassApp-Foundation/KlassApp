<?php

namespace Tests\Unit;

use App\Support\DemoRosterNames;
use Tests\TestCase;

class DemoRosterNamesTest extends TestCase
{
    public function test_demo_seeders_do_not_repeat_a_student_name_inside_a_school(): void
    {
        $counts = [
            'junior' => 4 + 4 + 4 + 6 + 6 + 6 + 6 + 10 + 10 + 10,
            'senior' => 12 + 12 + 10 + 10 + 8 + 8,
            'academy' => 4 + 4 + 4 + (6 * 4) + (10 * 3) + 5 + 5 + 4 + 4,
        ];

        foreach ($counts as $school => $count) {
            $names = DemoRosterNames::names($count);
            $this->assertCount($count, $names, $school);
            $this->assertSame(array_values(array_unique($names)), $names, $school);
        }

        foreach (['DemoJuniorSchoolSeeder.php', 'DemoSeniorSchoolSeeder.php', 'DemoAcademySeeder.php'] as $file) {
            $source = file_get_contents(base_path('database/seeders/'.$file));
            $this->assertStringContainsString('DemoRosterNames::names(array_sum($plan))', $source, $file);
        }
    }
}
