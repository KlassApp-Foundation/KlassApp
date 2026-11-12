<?php

namespace Tests\Feature\Parent;

use App\Enums\ToshiMode;
use App\Models\Academics\Marks;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** Parents and learners see marks only after release, and the phone list shows the KLS number. */
class ParentMarksPhoneTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
        Cache::flush();
        $this->school->forceFill([
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ])->save();

        $this->draftMark('English', 74);
        $maths = $this->draftMark('Mathematics', 62);
        $maths->update(['marks' => null, 'grade' => 'AB', 'absent' => true]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Phone Parent',
        ]);
        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Phone',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'status' => 1,
        ]);
    }

    public function test_a_parent_sees_nothing_before_release_and_the_card_after(): void
    {
        $this->school->refresh();
        $this->assertSame(ToshiMode::Preview, $this->school->toshi_mode);
        $this->assertSame(0, (int) $this->school->toshi_enabled);

        $before = $this->actingAs($this->parent)
            ->get(route('parent.children.grades', $this->student->id))
            ->assertOk();
        $before->assertSee('No report card has been released yet');
        $before->assertDontSee('data-testid="parent-marks-phone"', false);
        $before->assertDontSee('>74<', false);
        $before->assertDontSee('>ABS<', false);

        $learner = $this->actingAs($this->student)
            ->get(route('student.marks'))
            ->assertOk();
        $learner->assertSee('No report card has been released yet');
        $learner->assertDontSee('data-testid="parent-marks-phone"', false);

        $this->release('English');
        $this->release('Mathematics');

        $after = $this->actingAs($this->parent)
            ->get(route('parent.children.grades', $this->student->id))
            ->assertOk();
        $after->assertSee('data-testid="parent-marks-phone"', false);
        $after->assertSee('data-testid="parent-marks-phone-kls"', false);
        $after->assertSee('KLS0000431');
        $after->assertSee('ENGLISH');
        $after->assertSee('>74<', false);
        $after->assertSee('MATHEMATICS');
        $after->assertSee('>ABS<', false);
        $after->assertDontSee('No report card has been released yet');

        $this->assertSame('AB', Marks::query()->where('exam_id', $this->exams['Mathematics']->id)->value('grade'));

        $this->actingAs($this->student)
            ->get(route('student.marks'))
            ->assertOk()
            ->assertSee('data-testid="parent-marks-phone-kls"', false)
            ->assertSee('KLS0000431')
            ->assertSee('>74<', false)
            ->assertSee('>ABS<', false);
    }
}
