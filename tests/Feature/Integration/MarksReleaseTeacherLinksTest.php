<?php

namespace Tests\Feature\Integration;

use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/**
 * ClassListOrder::orderLinks() joins `sections`, which has its own class_teacher_id. The teacher
 * branch of MarksReleaseService::links() must qualify its columns (standards_link.*) or the query
 * fails with an ambiguous column.
 */
class MarksReleaseTeacherLinksTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
    }

    public function test_a_stream_teacher_gets_the_stream_that_names_them(): void
    {
        $this->link->forceFill(['class_teacher_id' => $this->teacher->id])->save();

        $links = app(MarksReleaseService::class)->links($this->teacher);

        $this->assertSame([$this->link->id], $links->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_a_class_head_gets_every_stream_of_the_class(): void
    {
        $this->section->forceFill(['class_teacher_id' => $this->teacher->id])->save();

        $links = app(MarksReleaseService::class)->links($this->teacher);

        $this->assertSame([$this->link->id], $links->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_another_teacher_gets_nothing(): void
    {
        $other = \App\Models\User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id]);
        $this->link->forceFill(['class_teacher_id' => $this->teacher->id])->save();

        $this->assertTrue(app(MarksReleaseService::class)->links($other)->isEmpty());
    }
}
