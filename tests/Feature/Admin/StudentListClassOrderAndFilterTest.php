<?php

namespace Tests\Feature\Admin;

use App\Livewire\ClassRoster\Index;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Riverside-like roster: nursery, then primary, with P.5 split into streams.
 * Lists follow standards.order and then the stream name. Picking one stream
 * does not return the other stream or another class in the same level.
 */
class StudentListClassOrderAndFilterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    /** @var array<string, StandardLink> */
    private array $links = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Riverside Primary',
            'email' => 'riverside-filter@example.test',
            'phone' => '+256700111000',
            'slug' => 'riverside-filter',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $nursery = $this->standard('nursery', 1);
        $primary = $this->standard('primary', 2);
        $olevel = $this->standard('o-level', 3);

        // Created out of level order so an id sort would be wrong.
        $this->links['s1'] = $this->link($olevel, 'S.1', 'East');
        $this->links['p1'] = $this->link($primary, 'P.1', null);
        $this->links['baby'] = $this->link($nursery, 'Baby Class', null);
        $this->links['p5a'] = $this->link($primary, 'P.5', 'A');
        // P.5 B shares P.5's section, the way the junior demo stores streams.
        $this->links['p5b'] = $this->link($primary, 'P.5', 'B', (int) $this->links['p5a']->section_id);

        $this->enrol('Amina Baby', $this->links['baby']);
        $this->enrol('Brian One', $this->links['p1']);
        $this->enrol('Carol Stream', $this->links['p5a']);
        $this->enrol('David Stream', $this->links['p5b']);
        $this->enrol('Eva Senior', $this->links['s1']);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Ada Nakato',
            'email' => 'ada.riverside-filter@example.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Ada',
            'lastname' => 'Nakato',
        ]);
    }

    public function test_student_class_picker_follows_level_then_stream(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/students')->assertOk()->getContent();

        $labels = $this->pickerLabels($html);
        $this->assertSame(
            ['Baby Class', 'P.1', 'P.5 · A', 'P.5 · B', 'S.1 · East'],
            $labels
        );
        $this->assertStringContainsString('standard='.$this->links['p5a']->id, $html);
        $this->assertStringContainsString('standard='.$this->links['p5b']->id, $html);
    }

    public function test_picking_a_stream_returns_only_that_stream(): void
    {
        $streamA = $this->actingAs($this->admin)->get('/admin/students?standard='.$this->links['p5a']->id);
        $streamA->assertOk();
        $streamA->assertSee('Carol Stream');
        $streamA->assertDontSee('David Stream');
        $streamA->assertDontSee('Brian One');
        $streamA->assertDontSee('Amina Baby');
        $streamA->assertDontSee('Eva Senior');

        $streamB = $this->actingAs($this->admin)->get('/admin/students?standard='.$this->links['p5b']->id);
        $streamB->assertOk();
        $streamB->assertSee('David Stream');
        $streamB->assertDontSee('Carol Stream');
        $streamB->assertDontSee('Brian One');
    }

    public function test_picking_a_class_includes_its_name_encoded_streams(): void
    {
        $primary = Standard::query()->where('school_id', $this->school->id)->where('name', 'primary')->firstOrFail();
        $base = $this->link($primary, 'P.6', null);
        $eastSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.6 East',
            'status' => 1,
        ]);
        $east = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $primary->id,
            'section_id' => $eastSection->id,
            'stream' => null,
            'status' => 1,
        ]);
        $this->enrol('Farida East', $east);

        $page = $this->actingAs($this->admin)->get('/admin/students?standard='.$base->id);
        $page->assertOk();
        $page->assertSee('Farida East');
        $page->assertDontSee('Carol Stream');
    }

    public function test_classes_page_orders_by_level_and_filters_from_a_dropdown(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Index::class);
        $html = $component->html();

        $this->assertStringContainsString('data-testid="ct-filter-class"', $html);
        $this->assertStringNotContainsString('data-testid="ct-filter-search"', $html);
        $this->assertStringNotContainsString('Search P.1, P.7', $html);

        $babyPos = strpos($html, 'text-slate-950">Baby Class');
        $p5Pos = strpos($html, 'text-slate-950">P.5');
        $s1Pos = strpos($html, 'text-slate-950">S.1');
        $this->assertNotFalse($babyPos);
        $this->assertNotFalse($p5Pos);
        $this->assertNotFalse($s1Pos);
        $this->assertLessThan($p5Pos, $babyPos);
        $this->assertLessThan($s1Pos, $p5Pos);

        $component->set('classId', (int) $this->links['p5a']->section_id);
        $filtered = $component->html();
        $this->assertStringContainsString('text-slate-950">P.5', $filtered);
        $this->assertStringNotContainsString('text-slate-950">Baby Class', $filtered);
        $this->assertStringNotContainsString('text-slate-950">S.1', $filtered);
    }

    public function test_report_cards_list_classes_by_level_then_stream(): void
    {
        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term III',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-11-30',
            'status' => 'current',
        ]);
        $type = ExamType::create([
            'name' => 'End of term',
            'code' => 'EOT-RIVERSIDE',
            'contributes_to_report_total' => true,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->links['p1']->standard_id,
            'section_id' => $this->links['p1']->section_id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        foreach ($this->links as $link) {
            Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $this->school->id,
                'standard_id' => $link->standard_id,
                'section_id' => $link->section_id,
                'subject_id' => $subject->id,
                'teacher_id' => $this->admin->id,
                'exam_type_id' => $type->id,
                'academic_term_id' => $term->id,
                'academic_year_id' => $this->year->id,
                'scheduled_at' => '2026-11-01',
                'status' => 'submitted',
            ]));
        }

        $html = $this->actingAs($this->admin)->get('/admin/reports/cards')->assertOk()->getContent();
        $baby = strpos($html, 'Baby Class');
        $p5a = strpos($html, 'P.5 · A');
        $p5b = strpos($html, 'P.5 · B');
        $senior = strpos($html, 'S.1 · East');
        $this->assertNotFalse($baby);
        $this->assertNotFalse($p5a);
        $this->assertNotFalse($p5b);
        $this->assertNotFalse($senior);
        $this->assertLessThan($p5a, $baby);
        $this->assertLessThan($p5b, $p5a);
        $this->assertLessThan($senior, $p5b);
    }

    private function pickerLabels(string $html): array
    {
        preg_match('/data-chip="class".*?<div class="people-picker-menu"[^>]*>(.*?)<\/div>/s', $html, $menu);
        $this->assertNotEmpty($menu[1] ?? null, 'Class picker did not render');
        preg_match_all('/<a role="listitem"[^>]*>([^<]+)<\/a>/', $menu[1], $labels);

        return array_map('trim', $labels[1]);
    }

    private function standard(string $name, int $order): Standard
    {
        return Standard::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'order' => $order,
            'status' => 1,
        ]);
    }

    private function link(Standard $standard, string $sectionName, ?string $stream, ?int $sectionId = null): StandardLink
    {
        if ($sectionId === null) {
            $sectionId = Section::create([
                'school_id' => $this->school->id,
                'name' => $sectionName,
                'status' => 1,
            ])->id;
        }

        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $sectionId,
            'stream' => $stream,
            'status' => 1,
        ]);
    }

    private function enrol(string $name, StandardLink $link): void
    {
        $student = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@riverside-filter.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? '',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
    }
}
