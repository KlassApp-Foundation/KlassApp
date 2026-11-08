<?php

namespace Tests\Feature;

use App\Livewire\ClassRoster\Show;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Class roster page for a base class whose streams are their own sections.
 *
 * Same Riverside-shaped fixture as BaseLinkHeadRegisterTest, anonymized: streams are the sections
 * "Primary Four E" and "Primary Four W", tied to the base "Primary Four" only by name prefix.
 * Every enrolment sits on the stream links (60 and 58), none on the base link.
 */
class ClassRosterStreamsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Standard $standard;

    private Section $base;

    private Section $eastSection;

    private Section $westSection;

    private StandardLink $baseLink;

    private StandardLink $eastLink;

    private StandardLink $westLink;

    private User $head;

    private User $westTeacher;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Synthetic Class School',
            'slug' => 'roster-'.uniqid(),
            'email' => 'roster-'.uniqid().'@t.sch.ug',
            'phone' => '+256707'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 4, 'status' => 1]);

        $this->head = $this->user(5, 'Class Head');
        $this->westTeacher = $this->user(5, 'West Teacher');
        $this->admin = $this->user(3, 'School Admin');

        $this->base = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Four', 'status' => 1, 'class_teacher_id' => null]);
        $this->eastSection = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Four E', 'status' => 1, 'class_teacher_id' => $this->head->id]);
        $this->westSection = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Four W', 'status' => 1, 'class_teacher_id' => $this->westTeacher->id]);

        $this->baseLink = $this->link($this->base, $this->head->id, $this->year);
        $this->eastLink = $this->link($this->eastSection, $this->head->id, $this->year);
        $this->westLink = $this->link($this->westSection, $this->westTeacher->id, $this->year);

        $this->students($this->eastLink, 'EAST', 60);
        $this->students($this->westLink, 'WEST', 58);
    }

    private function page(User $user, ?Section $section = null)
    {
        return Livewire::actingAs($user)->test(Show::class, [
            'sectionId' => ($section ?? $this->base)->id,
            'academicYearId' => $this->year->id,
        ]);
    }

    public function test_admin_base_page_lists_both_streams_with_their_students(): void
    {
        $page = $this->page($this->admin)->assertOk();
        $page->assertSeeHtml('data-testid="stream-count-'.$this->eastLink->id.'">(60)');
        $page->assertSeeHtml('data-testid="stream-count-'.$this->westLink->id.'">(58)');
        $page->assertDontSee('Main stream');
        $page->assertDontSee('No streams configured');
        $page->assertSee('EAST 1');

        $page->call('selectStream', $this->westLink->id)->assertSee('58 students')->assertSee('WEST 1')->assertDontSee('EAST 1');
    }

    public function test_a_stream_section_id_opens_the_same_class_page(): void
    {
        $this->page($this->admin, $this->eastSection)->assertOk()
            ->assertSeeHtml('data-testid="stream-count-'.$this->westLink->id.'">(58)');
    }

    public function test_the_west_only_teacher_sees_only_west(): void
    {
        $page = $this->page($this->westTeacher)->assertOk();
        $page->assertSeeHtml('data-testid="stream-count-'.$this->westLink->id.'">(58)');
        $page->assertDontSeeHtml('data-testid="stream-count-'.$this->eastLink->id.'"');
        $page->assertSee('WEST 1')->assertDontSee('EAST 1');

        // Asking for the East stream does not widen the view.
        $page->call('selectStream', $this->eastLink->id)->assertSee('WEST 1')->assertDontSee('EAST 1');

        $this->page($this->westTeacher, $this->westSection)->assertOk()->assertDontSee('EAST 1');
    }

    public function test_the_class_head_sees_every_stream_of_their_class_and_cannot_open_another_class(): void
    {
        $page = $this->page($this->head)->assertOk();
        $page->assertSeeHtml('data-testid="stream-count-'.$this->eastLink->id.'">(60)');
        $page->assertSeeHtml('data-testid="stream-count-'.$this->westLink->id.'">(58)');
        $page->call('selectStream', $this->westLink->id)->assertSee('WEST 1');

        $other = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Five', 'status' => 1, 'class_teacher_id' => $this->westTeacher->id]);
        $otherLink = $this->link($other, $this->westTeacher->id, $this->year);
        $this->students($otherLink, 'FIVE', 3);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->head)->test(Show::class, ['sectionId' => $other->id, 'academicYearId' => $this->year->id]);
    }

    public function test_the_head_cannot_open_another_class_that_has_streams(): void
    {
        $five = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Five', 'status' => 1, 'class_teacher_id' => null]);
        $fiveA = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Five A', 'status' => 1, 'class_teacher_id' => $this->westTeacher->id]);
        $this->link($five, $this->westTeacher->id, $this->year);
        $this->students($this->link($fiveA, $this->westTeacher->id, $this->year), 'FIVEA', 2);

        Livewire::actingAs($this->head)->test(Show::class, ['sectionId' => $five->id, 'academicYearId' => $this->year->id])
            ->assertStatus(404);
    }

    public function test_another_school_class_is_not_found(): void
    {
        $school = School::create([
            'name' => 'Other Synthetic School', 'slug' => 'other-'.uniqid(), 'email' => 'o-'.uniqid().'@t.sch.ug',
            'phone' => '+256707'.random_int(100000, 999999), 'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $foreign = Section::create(['school_id' => $school->id, 'name' => 'Primary Four', 'status' => 1]);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($this->admin)->test(Show::class, ['sectionId' => $foreign->id, 'academicYearId' => $this->year->id]);
    }

    public function test_a_class_without_streams_is_unchanged(): void
    {
        $solo = Section::create(['school_id' => $this->school->id, 'name' => 'Primary Six', 'status' => 1, 'class_teacher_id' => $this->westTeacher->id]);
        $soloLink = $this->link($solo, $this->westTeacher->id, $this->year);
        $this->students($soloLink, 'SIX', 4);

        $page = $this->page($this->admin, $solo)->assertOk();
        $page->assertSee('Main stream')->assertSee('4 students')->assertSee('SIX 1');
        $page->assertDontSeeHtml('data-testid="stream-count-');

        $this->page($this->westTeacher, $solo)->assertOk()->assertSee('SIX 1');
    }

    private function link(Section $section, ?int $teacherId, AcademicYear $year): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacherId,
            'stream' => null,
            'status' => 1,
        ]);
    }

    /**
     * @return list<int>
     */
    private function students(StandardLink $link, string $side, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = $this->user(6, $side.' '.$i);
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'user_id' => $user->id,
                'standardLink_id' => $link->id,
            ]);
            $ids[] = (int) $user->id;
        }

        return $ids;
    }

    private function user(int $group, string $name, ?School $school = null): User
    {
        $school = $school ?: $this->school;
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@shape.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? 'Person',
        ]);

        return $user;
    }
}
