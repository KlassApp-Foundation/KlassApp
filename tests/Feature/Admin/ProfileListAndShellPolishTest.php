<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\ParentProfileTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Profiles, lists and the app shell: wording, layout and one-action-by-state rules.
 * Each test is named after its register ID (F1 to F14). Foreign keys are enforced and the
 * app runs on a non-UTC timezone (AGENTS.md rules 35 and 42).
 */
class ProfileListAndShellPolishTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private StandardLink $link;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
        Carbon::setTestNow(Carbon::parse('2026-10-15 09:00:00', 'UTC'));
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        config(['dashboard.v2_enabled' => true]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Polish School',
            'slug' => 'polish-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'y',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id, 'name' => 'Term III',
            'starts_on' => '2026-09-01', 'ends_on' => '2026-11-30', 'status' => 'current',
        ]);
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.6', 'status' => 1]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'stream' => 'B', 'status' => 1,
        ]);
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3, 'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->admin->id, 'school_id' => $this->school->id, 'usergroup_id' => 3,
            'firstname' => 'Daniel', 'lastname' => 'Mukasa', 'status' => 'active',
        ]);
        $this->teacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5, 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_F1_long_profile_values_wrap_inside_their_card(): void
    {
        $parent = $this->parent('parent33@junior.demo.klassapp.example');

        $this->actingAs($this->admin)
            ->get('/admin/parent/show/'.$parent->name)
            ->assertOk()
            ->assertSee('data-person-profile', false)
            ->assertSee('parent33@junior.demo.klassapp.example');

        $rule = $this->cssRule(public_path('css/dashboard-refresh.css'), '[data-person-profile] dd');
        $this->assertNotNull($rule, 'The shared profile component has no wrapping rule for its values.');
        $this->assertStringContainsString('overflow-wrap: anywhere', $rule);
        $this->assertStringContainsString('min-width: 0', $rule);
    }

    public function test_F2_bulk_bar_shows_from_one_selected_and_stays_one_row_on_phones(): void
    {
        $this->student('Amina', 'female');

        $html = $this->actingAs($this->admin)->get('/admin/students')->assertOk()->getContent();

        // Select mode with nothing selected: one hint line, no bar.
        $this->assertMatchesRegularExpression('/<p class="people-bulk-hint" data-people-hint hidden>Select rows to export<\/p>/', $html);
        // Phones: Clear selection and Done live in the ⋯ menu so the bar is one row.
        $this->assertStringContainsString('data-people-bulk-more', $html);
        $this->assertMatchesRegularExpression('/<div class="people-bulk-more-menu">\s*<button type="button" data-people-clear>Clear selection<\/button>\s*<button type="button" data-people-done>Done<\/button>/', $html);
        // The bar keeps Export, Clear selection and Done; no Message or Move to class yet.
        $bar = substr($html, strpos($html, 'data-people-bulk '), 2000);
        $bar = substr($bar, 0, strpos($bar, '</details>'));
        $this->assertStringContainsString('>Export</a>', $bar);
        $this->assertStringNotContainsString('Message', $bar);
        $this->assertStringNotContainsString('Move to class', $bar);

        $js = file_get_contents(public_path('js/people-list.js'));
        $this->assertStringContainsString('bulk.hidden = !selecting() || boxes.length === 0;', $js);
        $this->assertStringContainsString('hint.hidden = !selecting() || boxes.length > 0;', $js);

        $css = file_get_contents(public_path('css/people-list.css'));
        $phone = substr($css, strpos($css, '@media (max-width: 760px)'));
        $phone = substr($phone, 0, strpos($phone, "\n}") + 2);
        $this->assertStringContainsString('.people-bulk { flex-wrap: nowrap; }', $phone);
        $this->assertStringContainsString('.people-bulk-wide { display: none; }', $phone);
        $this->assertStringContainsString('.people-bulk-more { display: block; }', $phone);
    }

    public function test_F4_report_cards_tile_shows_generate_until_every_card_exists(): void
    {
        $amina = $this->student('Amina', 'female');
        $this->student('Brian', 'male');
        $type = $this->examType('End of term');
        $exam = $this->exam('Mathematics', $type);
        $this->mark($exam, $amina, 70);

        $tile = $this->reportTile($this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->getContent());

        $this->assertSame(1, substr_count($tile, '<a '), 'The report cards tile must show one action.');
        $this->assertStringContainsString('>Generate</a>', $tile);
        $this->assertStringNotContainsString('Send on WhatsApp', $tile);
    }

    public function test_F4_report_cards_tile_shows_send_on_whatsapp_once_all_exist(): void
    {
        $amina = $this->student('Amina', 'female');
        $brian = $this->student('Brian', 'male');
        $type = $this->examType('End of term');
        $exam = $this->exam('Mathematics', $type);
        $this->mark($exam, $amina, 70);
        $this->mark($exam, $brian, 60);

        $tile = $this->reportTile($this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->getContent());

        $this->assertSame(1, substr_count($tile, '<a '), 'The report cards tile must show one action.');
        $this->assertStringContainsString('>Send on WhatsApp</a>', $tile);
        $this->assertStringNotContainsString('Generate', $tile);
    }

    public function test_F5_latest_exam_shows_the_exam_average_grade_and_position(): void
    {
        $amina = $this->student('Amina', 'female');
        $brian = $this->student('Brian', 'male');
        $type = $this->examType('End of Term Examination');
        $math = $this->exam('MATHEMATICS', $type);
        $social = $this->exam('SOCIAL STUDIES', $type);
        $this->mark($math, $amina, 80, 'D2');
        $this->mark($social, $amina, 52, 'C6');
        $this->mark($math, $brian, 90, 'D1');
        $this->mark($social, $brian, 70, 'C3');

        $this->actingAs($this->admin)
            ->get('/admin/students/'.$amina->id)
            ->assertOk()
            ->assertSee('data-testid="exam-label" style="margin:0;font-size:13px;color:#475569;">Latest exam</p>', false)
            ->assertSee('66% · ', false)
            ->assertSee('End of Term Examination · 2 of 2', false)
            ->assertDontSee('SOCIAL STUDIES — ', false)
            ->assertDontSee('52% · C6', false);
    }

    public function test_F6_student_header_shows_the_key_facts_row(): void
    {
        $amina = $this->student('Amina', 'female', ['date_of_birth' => '2015-08-06', 'joining_date' => '2024-02-01']);

        $html = $this->actingAs($this->admin)->get('/admin/students/'.$amina->id)->assertOk()->getContent();
        $facts = $this->between($html, 'data-testid="person-facts"', '</p>');

        $this->assertStringContainsString('<span data-fact="class" >P.6 · B</span>', $facts);
        $this->assertStringContainsString('<span data-fact="kls"  data-testid="person-kls" >'.$amina->registration_number.'</span>', $facts);
        $this->assertStringContainsString('Age 11 (born 6 Aug 2015)', $facts);
        $this->assertStringContainsString('Female', $facts);
        $this->assertStringContainsString('Joined 1 Feb 2024', $facts);
    }

    public function test_F6_missing_facts_read_not_given(): void
    {
        $brian = $this->student('Brian', null);

        $html = $this->actingAs($this->admin)->get('/admin/students/'.$brian->id)->assertOk()->getContent();
        $facts = $this->between($html, 'data-testid="person-facts"', '</p>');

        $this->assertStringContainsString('Age not given', $facts);
        $this->assertStringContainsString('Gender not given', $facts);
        $this->assertStringContainsString('Joined date not given', $facts);
        $this->assertStringNotContainsString('Not recorded', $html);
        $this->assertStringNotContainsString('Not specified', $html);
    }

    public function test_F7_parent_fees_tile_shows_the_currency_on_every_amount(): void
    {
        DB::table('school_details')->insert([
            'school_id' => $this->school->id, 'meta_key' => 'currency', 'meta_value' => 'UGX',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $amina = $this->student('Amina', 'female');
        $parent = $this->parent('grace@t.sch.ug', $amina);
        FeesCategories::create([
            'school_id' => $this->school->id, 'standard_id' => $this->link->standard_id, 'name' => 'Tuition', 'amount' => 100000,
        ]);
        FeePayment::create([
            'school_id' => $this->school->id, 'user_id' => $amina->id, 'amount' => 40000,
            'paid_on' => '2026-10-01', 'recorded_by' => $this->admin->id,
        ]);

        $html = $this->actingAs($this->admin)->get('/admin/parent/show/'.$parent->name)->assertOk()->getContent();
        $tile = $this->between($html, 'data-testid="kpi-fees"', '</article>');

        $this->assertStringContainsString('UGX 60,000', $tile);
        $this->assertStringContainsString('Paid UGX 40,000 of UGX 100,000', $tile);
        $this->assertStringContainsString('Partly paid', $tile);
        $this->assertStringNotContainsString('Paid 40,000', $tile);
    }

    public function test_F10_staff_viewing_a_parent_read_children(): void
    {
        $parent = $this->parent('grace@t.sch.ug');

        $html = $this->actingAs($this->admin)->get('/admin/parent/show/'.$parent->name)->assertOk()->getContent();
        $tabs = $this->between($html, 'data-testid="person-profile-tabs"', '</nav>');

        $this->assertMatchesRegularExpression('/data-tab="children"[^>]*>Children<\/a>/', $tabs);
        $this->assertStringNotContainsString('My children', $tabs);
        $this->assertSame('My children', ParentProfileTabs::labels(true)['children']);
        $this->assertSame('Children', ParentProfileTabs::labels()['children']);
    }

    public function test_F11_sidebar_footer_hides_coming_soon_when_toshi_is_on_for_the_school(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-testid="dashboard-sidebar-footer"', false)
            ->assertDontSee('data-testid="sidebar-toshi-soon"', false)
            ->assertDontSee('Toshi · coming soon');
    }

    public function test_F11_sidebar_footer_keeps_coming_soon_when_toshi_is_off(): void
    {
        $noSchool = User::factory()->create(['school_id' => null, 'usergroup_id' => 3, 'status' => 'active']);
        $this->actingAs($noSchool);

        $html = view('layouts.partials.sidebar-footer', ['notifyMode' => null])->render();

        $this->assertStringContainsString('Toshi · coming soon', $html);
    }

    public function test_F12_account_menu_items_stay_on_one_line(): void
    {
        $css = public_path('css/dashboard-refresh.css');
        $item = $this->cssRule($css, '.account-card__item');
        $menu = $this->cssRule($css, '.account-card__menu.user-dtl');
        $portaled = $this->cssRule($css, '.account-card__menu.user-dtl.is-portaled');

        $this->assertStringContainsString('white-space: nowrap', $item);
        $this->assertStringContainsString('min-width: 224px', $menu);
        $this->assertStringContainsString('min-width: 224px', $portaled);
        $this->assertStringContainsString('cardWidth = Math.max(224, cardWidth);', file_get_contents(public_path('js/custom.js')));
    }

    public function test_F13_search_placeholders_are_short_and_the_box_is_wide_enough(): void
    {
        $this->student('Amina', 'female');

        $this->actingAs($this->admin)->get('/admin/students')->assertOk()
            ->assertSee('placeholder="Search name or KLS number"', false);
        $this->actingAs($this->admin)->get('/admin/teachers')->assertOk()
            ->assertSee('placeholder="Search name or email"', false);
        $this->actingAs($this->admin)->get('/admin/parents')->assertOk()
            ->assertSee('placeholder="Search name or phone"', false);

        $rule = $this->cssRule(public_path('css/people-list.css'), '.people-search');
        $this->assertStringContainsString('min-width: 280px', $rule);
    }

    public function test_F14_profile_names_use_the_page_title_letter_spacing(): void
    {
        $amina = $this->student('Amina', 'female');
        $parent = $this->parent('grace@t.sch.ug');

        foreach (['/admin/students/'.$amina->id, '/admin/parent/show/'.$parent->name] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/<h1 style="[^"]*letter-spacing:-0\.02em;[^"]*">/', $html, $url);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function student(string $first, ?string $gender, array $profile = []): User
    {
        static $n = 50;
        $kls = 'KLS03800'.str_pad((string) $n++, 2, '0', STR_PAD_LEFT);
        $user = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'status' => 'active',
            'name' => strtolower($first).uniqid(), 'registration_number' => $kls,
        ]);
        Userprofile::create(array_merge([
            'user_id' => $user->id, 'school_id' => $this->school->id, 'usergroup_id' => 6,
            'firstname' => $first, 'lastname' => 'Pupil', 'gender' => $gender, 'status' => 'active',
        ], $profile));
        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->link->academic_year_id,
            'user_id' => $user->id, 'standardLink_id' => $this->link->id, 'klassapp_student_id' => $kls,
        ]);

        return $user;
    }

    private function parent(string $email, ?User $child = null): User
    {
        $parent = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 7, 'status' => 'active',
            'name' => 'parent-'.uniqid(), 'email' => $email,
        ]);
        Userprofile::create([
            'user_id' => $parent->id, 'school_id' => $this->school->id, 'usergroup_id' => 7,
            'firstname' => 'Grace', 'lastname' => 'Nakato', 'status' => 'active',
        ]);
        if ($child) {
            StudentParentLink::create([
                'school_id' => $this->school->id, 'parent_id' => $parent->id, 'student_id' => $child->id, 'status' => 1,
            ]);
        }

        return $parent;
    }

    private function examType(string $name): ExamType
    {
        return ExamType::create(['name' => $name, 'code' => 'EOT', 'contributes_to_report_total' => true]);
    }

    private function exam(string $subjectName, ExamType $type): Exam
    {
        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->link->academic_year_id,
            'standard_id' => $this->link->standard_id, 'section_id' => $this->link->section_id,
            'name' => $subjectName, 'code' => strtoupper(substr($subjectName, 0, 3)), 'type' => 'core',
        ]);
        $exam = new Exam([
            'school_id' => $this->school->id, 'standard_id' => $this->link->standard_id,
            'section_id' => $this->link->section_id, 'academic_year_id' => $this->link->academic_year_id,
            'academic_term_id' => AcademicTerm::where('school_id', $this->school->id)->value('id'),
            'subject_id' => $subject->id, 'teacher_id' => $this->teacher->id, 'status' => 'done',
        ]);
        $exam->exam_type_id = $type->id;
        $exam->save();

        return $exam;
    }

    private function mark(Exam $exam, User $student, float $score, string $grade = 'C3'): void
    {
        Marks::create([
            'student_id' => $student->id, 'teacher_id' => $this->teacher->id, 'school_id' => $this->school->id,
            'subject_id' => $exam->subject_id, 'exam_id' => $exam->id, 'section_id' => $this->link->section_id,
            'marks' => $score, 'grade' => $grade,
        ]);
    }

    private function reportTile(string $html): string
    {
        $start = strpos($html, 'data-testid="dashboard-v2-kpi-reports"');
        $this->assertNotFalse($start, 'Report cards tile not rendered.');
        $tile = substr($html, $start);

        return substr($tile, 0, strpos($tile, '</div>'));
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, $start.' not rendered.');
        $rest = substr($html, $from);

        return substr($rest, 0, strpos($rest, $end) + strlen($end));
    }

    /** The declarations of the first rule whose selector list contains $selector exactly. */
    private function cssRule(string $file, string $selector): ?string
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($file));
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
        foreach ($rules as $rule) {
            $selectors = array_map('trim', explode(',', $rule[1]));
            if (in_array($selector, $selectors, true)) {
                return $rule[2];
            }
        }

        return null;
    }
}
