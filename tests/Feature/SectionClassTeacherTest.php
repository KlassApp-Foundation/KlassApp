<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SectionClassTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_class_teacher_relationship_is_nullable_and_resolves(): void
    {
        $school = School::create([
            'name' => 'Section Teacher School',
            'slug' => 'section-teacher-school',
            'email' => 'section-teacher@test.sch.ug',
            'phone' => '0700000001',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
        ]);
        $unassigned = Section::create([
            'school_id' => $school->id,
            'name' => 'P1',
            'status' => 1,
        ]);
        $assigned = Section::create([
            'school_id' => $school->id,
            'name' => 'P2',
            'class_teacher_id' => $teacher->id,
            'status' => 1,
        ]);

        $this->assertNull($unassigned->classTeacher);
        $this->assertTrue($assigned->refresh()->classTeacher->is($teacher));
    }

    public function test_section_teacher_migration_can_roll_back_and_reapply_without_backfill(): void
    {
        $school = School::create([
            'name' => 'Migration Section School',
            'slug' => 'migration-section-school',
            'email' => 'migration-section@test.sch.ug',
            'phone' => '0700000002',
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P3',
            'status' => 1,
        ]);

        $this->assertTrue(Schema::hasColumn('sections', 'class_teacher_id'));
        $this->assertNull($section->class_teacher_id);

        $migration = require database_path(
            'migrations/2026_08_18_220314_add_class_teacher_id_to_sections_table.php'
        );
        $migration->down();

        $this->assertFalse(Schema::hasColumn('sections', 'class_teacher_id'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('sections', 'class_teacher_id'));
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'class_teacher_id' => null,
        ]);
    }
}
