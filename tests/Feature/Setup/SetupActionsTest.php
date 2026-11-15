<?php

namespace Tests\Feature\Setup;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ActivityLog;
use Tests\TestCase;

/**
 * Setup actions with no command before: make a term the current term, and link a section to a class.
 * Each has a screen (with a confirmation) and a command (dry run by default, --apply, one log line).
 * Invented schools and people only.
 */
class SetupActionsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $other;

    private AcademicYear $year;

    private User $admin;

    private Standard $standard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = $this->makeSchool('Setup School');
        $this->other = $this->makeSchool('Other Setup School');
        $this->year = $this->makeYear($this->school);
        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->admin = $this->user($this->school, 3, 'Setup Admin');
    }

    // ---- Terms ------------------------------------------------------------------------------

    public function test_the_terms_page_offers_make_this_the_current_term_only_for_a_term_that_is_not_current(): void
    {
        [$first, $second] = $this->terms($this->school, $this->year);

        $this->actingAs($this->admin)->get('/admin/academic-term')->assertOk()
            ->assertSee('data-testid="make-current-'.$second->id.'"', false)
            ->assertDontSee('data-testid="make-current-'.$first->id.'"', false)
            ->assertSee('Make this the current term')
            ->assertSee('Current term');
    }

    public function test_the_confirmation_page_names_the_term_and_the_one_that_stops_being_current(): void
    {
        [$first, $second] = $this->terms($this->school, $this->year);

        $this->actingAs($this->admin)->get('/admin/academic-term/'.$second->id.'/make-current')->assertOk()
            ->assertSee('data-testid="make-current-sentence"', false)
            ->assertSee($second->name)
            ->assertSee($first->name.' stops being the current term', false);
        $this->assertSame('current', $first->fresh()->status);
        $this->assertSame('next', $second->fresh()->status);
    }

    public function test_making_a_term_current_moves_the_old_one_logs_it_and_needs_the_confirmation(): void
    {
        [$first, $second] = $this->terms($this->school, $this->year);
        Log::spy();

        $this->actingAs($this->admin)->post('/admin/academic-term/'.$second->id.'/make-current', [])->assertSessionHasErrors('confirm');
        $this->assertSame('current', $first->fresh()->status, 'no confirmation, no change');

        $this->actingAs($this->admin)->post('/admin/academic-term/'.$second->id.'/make-current', ['confirm' => '1'])
            ->assertRedirect(route('admin.academic-term'));

        $this->assertSame('current', $second->fresh()->status);
        $this->assertSame('last', $first->fresh()->status);
        $this->assertSame(1, AcademicTerm::query()->where('school_id', $this->school->id)->where('status', 'current')->count());
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'setup.current-term' && ($context['school_id'] ?? null) === (int) $this->school->id && ($context['via'] ?? null) === 'screen')->once();
        $this->assertTrue(ActivityLog::query()->where('log_name', 'setup')->where('causer_id', $this->admin->id)->exists());
    }

    public function test_an_upcoming_term_is_the_one_that_follows_when_the_chosen_term_is_earlier(): void
    {
        $later = AcademicTerm::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'name' => 'Later', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01', 'status' => 'current']);
        $earlier = AcademicTerm::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'name' => 'Earlier', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-01', 'status' => 'last']);

        $this->actingAs($this->admin)->post('/admin/academic-term/'.$earlier->id.'/make-current', ['confirm' => '1'])->assertRedirect();

        $this->assertSame('current', $earlier->fresh()->status);
        $this->assertSame('next', $later->fresh()->status);
    }

    public function test_another_schools_term_gets_the_same_answer_as_a_missing_one_and_nothing_changes(): void
    {
        $foreignYear = $this->makeYear($this->other);
        [$foreignFirst, $foreignSecond] = $this->terms($this->other, $foreignYear);
        $missing = AcademicTerm::query()->max('id') + 500;

        foreach ([$foreignSecond->id, $missing] as $id) {
            $this->actingAs($this->admin)->get('/admin/academic-term/'.$id.'/make-current')->assertNotFound();
            $this->actingAs($this->admin)->post('/admin/academic-term/'.$id.'/make-current', ['confirm' => '1'])->assertNotFound();
        }
        $this->assertSame('current', $foreignFirst->fresh()->status);
        $this->assertSame('next', $foreignSecond->fresh()->status);
    }

    public function test_making_the_current_term_current_again_says_so_and_changes_nothing(): void
    {
        [$first] = $this->terms($this->school, $this->year);

        $this->actingAs($this->admin)->get('/admin/academic-term/'.$first->id.'/make-current')->assertOk()->assertSee('data-testid="make-current-already"', false);
        $this->actingAs($this->admin)->followingRedirects()->post('/admin/academic-term/'.$first->id.'/make-current', ['confirm' => '1'])->assertSee('already the current term');
        $this->assertSame(0, ActivityLog::query()->where('log_name', 'setup')->count());
    }

    public static function rolesRefused(): array
    {
        return ['head teacher' => [4], 'teacher' => [5], 'student' => [6], 'parent' => [7], 'accountant' => [11]];
    }

    #[DataProvider('rolesRefused')]
    public function test_other_roles_cannot_change_the_current_term_or_link_a_section(int $group): void
    {
        [$first, $second] = $this->terms($this->school, $this->year);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'Loose Section', 'status' => 1]);
        $user = $this->user($this->school, $group, 'Refused '.$group);

        foreach ([
            $this->actingAs($user)->post('/admin/academic-term/'.$second->id.'/make-current', ['confirm' => '1']),
            $this->actingAs($user)->get('/admin/academic-term/'.$second->id.'/make-current'),
            $this->actingAs($user)->post('/admin/classes/link-section/'.$section->id, ['standard_id' => $this->standard->id, 'confirm' => '1']),
            $this->actingAs($user)->get('/admin/classes/link-section/'.$section->id),
        ] as $response) {
            $this->assertNotSame(200, $response->getStatusCode());
            $this->assertContains($response->getStatusCode(), [302, 403, 404], 'role '.$group);
        }
        $this->assertSame('current', $first->fresh()->status);
        $this->assertSame(0, StandardLink::query()->count());
    }

    public function test_the_set_current_term_command_is_a_dry_run_unless_apply_is_given(): void
    {
        [$first, $second] = $this->terms($this->school, $this->year);
        Log::spy();

        $this->artisan('terms:set-current', ['school' => $this->school->id, 'term' => $second->id])
            ->expectsOutputToContain('Dry run')->assertExitCode(0);
        $this->assertSame('current', $first->fresh()->status);

        $this->artisan('terms:set-current', ['school' => $this->school->id, 'term' => $second->id, '--apply' => true])
            ->expectsOutputToContain('now the current term')->assertExitCode(0);
        $this->assertSame('current', $second->fresh()->status);
        $this->assertSame('last', $first->fresh()->status);
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'setup.current-term' && ($context['via'] ?? null) === 'command')->once();
    }

    public function test_the_set_current_term_command_refuses_another_schools_term_and_a_missing_school(): void
    {
        [, $foreign] = $this->terms($this->other, $this->makeYear($this->other));

        $this->artisan('terms:set-current', ['school' => $this->school->id, 'term' => $foreign->id, '--apply' => true])
            ->expectsOutputToContain('Term not found for this school.')->assertExitCode(1);
        $this->assertSame('next', $foreign->fresh()->status);

        $this->artisan('terms:set-current', ['school' => 999999, 'term' => $foreign->id, '--apply' => true])
            ->expectsOutputToContain('School not found.')->assertExitCode(1);
    }

    // ---- Classes ----------------------------------------------------------------------------

    public function test_the_classes_page_lists_sections_with_no_class_link_and_hides_the_list_when_none(): void
    {
        $loose = Section::create(['school_id' => $this->school->id, 'name' => 'Loose Section', 'status' => 1]);
        $linked = Section::create(['school_id' => $this->school->id, 'name' => 'Linked Section', 'status' => 1]);
        StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $linked->id, 'status' => 1]);
        Section::create(['school_id' => $this->other->id, 'name' => 'Foreign Loose Section', 'status' => 1]);

        $html = $this->actingAs($this->admin)->get('/admin/classes')->assertOk()
            ->assertSee('data-testid="unlinked-sections"', false)
            ->assertSee('data-testid="link-section-'.$loose->id.'"', false)
            ->assertSee('Link to a class')
            ->getContent();
        $this->assertStringNotContainsString('data-testid="link-section-'.$linked->id.'"', $html);
        $this->assertStringNotContainsString('Foreign Loose Section', $html);

        StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $loose->id, 'status' => 1]);
        $this->actingAs($this->admin)->get('/admin/classes')->assertOk()->assertDontSee('data-testid="unlinked-sections"', false);
    }

    public function test_linking_a_section_asks_first_then_creates_one_link_and_logs_it(): void
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'Loose Section', 'status' => 1]);
        Log::spy();

        $this->actingAs($this->admin)->get('/admin/classes/link-section/'.$section->id.'?standard='.$this->standard->id)->assertOk()
            ->assertSee('data-testid="link-section-sentence"', false)->assertSee('Loose Section')->assertSee('Link section to class');
        $this->assertSame(0, StandardLink::query()->count(), 'the confirmation page writes nothing');

        $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$section->id, ['standard_id' => $this->standard->id])->assertSessionHasErrors('confirm');
        $this->assertSame(0, StandardLink::query()->count());

        $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$section->id, ['standard_id' => $this->standard->id, 'confirm' => '1'])
            ->assertRedirect(route('admin.classes.index'));

        $link = StandardLink::query()->where('section_id', $section->id)->firstOrFail();
        $this->assertSame((int) $this->school->id, (int) $link->school_id);
        $this->assertSame((int) $this->year->id, (int) $link->academic_year_id);
        $this->assertSame((int) $this->standard->id, (int) $link->standard_id);
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'setup.link-section' && ($context['via'] ?? null) === 'screen')->once();
        $this->assertTrue(ActivityLog::query()->where('log_name', 'setup')->where('causer_id', $this->admin->id)->exists());
    }

    public function test_linking_the_same_section_twice_makes_one_link(): void
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'Loose Section', 'status' => 1]);
        $body = ['standard_id' => $this->standard->id, 'confirm' => '1'];

        $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$section->id, $body)->assertRedirect();
        $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$section->id, $body)->assertRedirect()->assertSessionHas('errormessage');

        $this->assertSame(1, StandardLink::query()->where('section_id', $section->id)->count());
    }

    public function test_another_schools_section_or_class_gets_the_same_answer_as_a_missing_one(): void
    {
        $foreignSection = Section::create(['school_id' => $this->other->id, 'name' => 'Foreign Section', 'status' => 1]);
        $foreignStandard = Standard::create(['school_id' => $this->other->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $ownSection = Section::create(['school_id' => $this->school->id, 'name' => 'Own Section', 'status' => 1]);
        $missingSection = Section::query()->max('id') + 500;
        $missingStandard = Standard::query()->max('id') + 500;

        foreach ([$foreignSection->id, $missingSection] as $sectionId) {
            $this->actingAs($this->admin)->get('/admin/classes/link-section/'.$sectionId)->assertNotFound();
            $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$sectionId, ['standard_id' => $this->standard->id, 'confirm' => '1'])->assertNotFound();
        }
        foreach ([$foreignStandard->id, $missingStandard] as $standardId) {
            $this->actingAs($this->admin)->get('/admin/classes/link-section/'.$ownSection->id.'?standard='.$standardId)->assertNotFound();
            $this->actingAs($this->admin)->post('/admin/classes/link-section/'.$ownSection->id, ['standard_id' => $standardId, 'confirm' => '1'])->assertNotFound();
        }
        $this->assertSame(0, StandardLink::query()->count());
    }

    public function test_the_link_section_command_is_a_dry_run_unless_apply_is_given(): void
    {
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'Loose Section', 'status' => 1]);
        Log::spy();

        $this->artisan('classes:link-section', ['school' => $this->school->id, 'section' => $section->id, 'standard' => $this->standard->id])
            ->expectsOutputToContain('Dry run')->assertExitCode(0);
        $this->assertSame(0, StandardLink::query()->count());

        $this->artisan('classes:link-section', ['school' => $this->school->id, 'section' => $section->id, 'standard' => $this->standard->id, '--apply' => true])
            ->expectsOutputToContain('Done')->assertExitCode(0);
        $this->assertSame(1, StandardLink::query()->where('section_id', $section->id)->count());
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'setup.link-section' && ($context['via'] ?? null) === 'command')->once();

        $this->artisan('classes:link-section', ['school' => $this->school->id, 'section' => $section->id, 'standard' => $this->standard->id, '--apply' => true])
            ->expectsOutputToContain('already linked')->assertExitCode(0);
        $this->assertSame(1, StandardLink::query()->where('section_id', $section->id)->count());
    }

    public function test_the_link_section_command_refuses_another_schools_ids(): void
    {
        $foreignSection = Section::create(['school_id' => $this->other->id, 'name' => 'Foreign Section', 'status' => 1]);
        $ownSection = Section::create(['school_id' => $this->school->id, 'name' => 'Own Section', 'status' => 1]);
        $foreignStandard = Standard::create(['school_id' => $this->other->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);

        $this->artisan('classes:link-section', ['school' => $this->school->id, 'section' => $foreignSection->id, 'standard' => $this->standard->id, '--apply' => true])
            ->expectsOutputToContain('not found for this school')->assertExitCode(1);
        $this->artisan('classes:link-section', ['school' => $this->school->id, 'section' => $ownSection->id, 'standard' => $foreignStandard->id, '--apply' => true])
            ->expectsOutputToContain('not found for this school')->assertExitCode(1);
        $this->assertSame(0, StandardLink::query()->count());
    }

    // ---- helpers ----------------------------------------------------------------------------

    /** @return array{0: AcademicTerm, 1: AcademicTerm} the current first term and a next second term */
    private function terms(School $school, AcademicYear $year): array
    {
        return [
            AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'First Term', 'starts_on' => '2026-01-10', 'ends_on' => '2026-04-10', 'status' => 'current']),
            AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Second Term', 'starts_on' => '2026-05-10', 'ends_on' => '2026-08-10', 'status' => 'next']),
        ];
    }

    private function makeYear(School $school): AcademicYear
    {
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        \Illuminate\Support\Facades\Cache::forget('academic_year_for_school_'.$school->id);

        return $year;
    }

    private function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name, 'slug' => 'su-'.uniqid(), 'email' => uniqid().'@su.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1,
        ]);
    }

    private function user(School $school, int $group, string $name): User
    {
        $user = User::create([
            'name' => $name.' '.uniqid(), 'email' => uniqid().'@su-user.test', 'password' => Hash::make('password123'),
            'usergroup_id' => $group, 'school_id' => $school->id, 'status' => 'active',
        ]);
        Userprofile::create(['user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => $group, 'status' => 'active']);

        return $user;
    }
}
