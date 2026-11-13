<?php

namespace Tests\Feature\Exams;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\SendRealSchoolToshiOnboardingToWizard;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Settings > Exam types: the shared catalog rows sit next to the types a school
 * adds for itself, and each school keeps its own toggle map in
 * schools.exam_type_preferences.
 */
class ExamTypeSettingsTest extends TestCase
{
    use RefreshDatabase;

    private School $riverside;

    private School $other;

    private ExamType $eot;

    private ExamType $bot;

    private User $admin;

    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class, SendRealSchoolToshiOnboardingToWizard::class]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->riverside = School::create([
            'name' => 'Riverside Test', 'slug' => 'riverside-'.uniqid(), 'email' => uniqid().'@rs.test.ug',
            'phone' => '0700000001', 'status' => 1,
        ]);
        $this->other = School::create([
            'name' => 'Other Test', 'slug' => 'other-'.uniqid(), 'email' => uniqid().'@other.test.ug',
            'phone' => '0700000002', 'status' => 1,
        ]);

        $this->eot = ExamType::create(['name' => 'End of Term Examination', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $this->bot = ExamType::create(['name' => 'Beginning of Term Examination', 'code' => 'BOT', 'contributes_to_report_total' => false]);

        $this->admin = $this->makeUser($this->riverside, 3, 'Riverside Admin');
        $this->otherAdmin = $this->makeUser($this->other, 3, 'Other Admin');

        $year = AcademicYear::create([
            'school_id' => $this->riverside->id, 'name' => '2026', 'description' => 'Current',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->riverside->id, 'academic_year_id' => $year->id,
            'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01',
        ]);
    }

    public function test_a_school_owned_type_is_invisible_to_another_school(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings/exam-types/create', [
                'name' => 'Mock Examination',
                'code' => 'mock',
                'contributes_to_report_total' => '1',
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $mine = ExamType::where('school_id', $this->riverside->id)->firstOrFail();
        $this->assertSame('Mock Examination', $mine->name);
        $this->assertSame('MOCK', $mine->code, 'the short code is stored upper case');
        $this->assertTrue($mine->contributes_to_report_total);
        $this->assertSame(0, ExamType::where('school_id', $this->other->id)->count(), 'the other school owns nothing');

        $this->actingAs($this->admin)
            ->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertSee('data-testid="exam-type-row-'.$mine->id.'"', false)
            ->assertSee('(your school)')
            ->assertSee('(shared)');

        // The school's own type is offered on its exam set form...
        $this->actingAs($this->admin)
            ->get('/admin/exams/sets/create')
            ->assertOk()
            ->assertSee('Mock Examination');

        // ...and nowhere on the other school's pages.
        $this->actingAs($this->otherAdmin)
            ->get('/admin/settings/exam-types')
            ->assertOk()
            ->assertDontSee('data-testid="exam-type-row-'.$mine->id.'"', false);

        $this->actingAs($this->otherAdmin)
            ->get('/admin/exams/sets/create')
            ->assertOk()
            ->assertDontSee('value="'.$mine->id.'"', false);
    }

    public function test_a_code_already_visible_to_the_school_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/settings/exam-types')
            ->post('/admin/settings/exam-types/create', ['name' => 'My EOT', 'code' => 'eot'])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, ExamType::where('school_id', $this->riverside->id)->count());
    }

    public function test_toggles_change_the_default_set(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings/exam-types', [
                'contributes' => [
                    $this->eot->id => '0',
                    $this->bot->id => '1',
                ],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $prefs = $this->riverside->fresh()->exam_type_preferences;
        $this->assertSame('0', $prefs[(string) $this->eot->id]);
        $this->assertSame('1', $prefs[(string) $this->bot->id]);

        // The exam set form starts on the toggled set, not on every type.
        $html = $this->actingAs($this->admin)->get('/admin/exams/sets/create')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/name="type_ids\[\]" value="'.$this->bot->id.'"\s+checked/', $html));
        $this->assertSame(0, preg_match('/name="type_ids\[\]" value="'.$this->eot->id.'"\s+checked/', $html));
    }

    public function test_includes_and_weights_survive_the_save(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings/exam-types', [
                'contributes' => [$this->eot->id => '1', $this->bot->id => '0'],
                'include' => [$this->eot->id => '1', $this->bot->id => '1'],
                'weight' => [$this->eot->id => '60', $this->bot->id => '40'],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $composition = $this->riverside->fresh()->report_composition;
        $this->assertTrue($composition[(string) $this->eot->id]['include']);
        $this->assertEquals(60.0, $composition[(string) $this->eot->id]['weight']);
        $this->assertTrue($composition[(string) $this->bot->id]['include']);
        $this->assertEquals(40.0, $composition[(string) $this->bot->id]['weight']);
    }

    public function test_weights_that_do_not_total_100_are_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/settings/exam-types')
            ->post('/admin/settings/exam-types', [
                'contributes' => [$this->eot->id => '1', $this->bot->id => '0'],
                'include' => [$this->eot->id => '0', $this->bot->id => '1'],
                'weight' => [$this->eot->id => '', $this->bot->id => '40'],
            ])
            ->assertRedirect('/admin/settings/exam-types')
            ->assertSessionHasErrors('weight');

        $this->assertNull($this->riverside->fresh()->report_composition);
        $this->assertNull($this->riverside->fresh()->exam_type_preferences);
    }

    public function test_the_unset_form_starts_at_end_of_term_only(): void
    {
        $raw = $this->actingAs($this->admin)->get('/admin/settings/exam-types')->assertOk()->getContent();
        $scripts = strpos($raw, '<!-- Scripts -->');
        $listener = strpos($raw, "document.addEventListener('DOMContentLoaded', function () {\n    var form = document.querySelector('[data-testid=\"report-composition-form\"]');");
        $this->assertNotFalse($scripts);
        $this->assertNotFalse($listener);
        $this->assertGreaterThan($scripts, $listener);
        $html = preg_replace('/\s+/', ' ', $raw);

        $this->assertMatchesRegularExpression('/data-testid="exam-type-include-'.$this->eot->id.'" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-testid="exam-type-include-'.$this->bot->id.'" checked/', $html);
        $this->assertMatchesRegularExpression('/name="weight\['.$this->eot->id.'\]"[^>]*value="100"/', $html);
        $this->assertStringContainsString('min-height:44px', $html);
        $this->assertStringContainsString('Save report settings', $html);
        $this->assertStringContainsString('End of Term only', $html);
        $this->assertStringContainsString('Mid 30 · End 70', $html);
        $this->assertStringContainsString('Mid Term 30 · End of Term 70', $html);
        $this->assertStringContainsString('All three equally', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('Counts on the report', $html);
        $this->assertStringContainsString('Not on the end-of-term report', $html);
        $this->assertStringContainsString('Adds up', $html);
        $this->assertStringContainsString('Applies from the next release.', $html);
        $this->assertStringContainsString('Reports already released keep the mix they were released with.', $html);
        $this->assertStringContainsString('Made up of: End of Term Examination 100%', $html);
        $this->assertStringContainsString('Report marks appear here after the counted exam sets are released.', $html);
        $this->assertStringContainsString('Each exam set has its own report', $html);
        $this->assertStringContainsString('Not released yet', $html);
        $this->assertStringContainsString('The current mix was saved with the school.', $html);
        $this->assertStringContainsString('Admins and head teachers can change this.', $html);
        $this->assertStringContainsString('Cancel', $html);
        $this->assertStringNotContainsString('Grace Achieng', $html);
        $this->assertStringNotContainsString('80 × 30%', $html);
    }

    public function test_a_head_teacher_sets_the_mix_for_their_own_school(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 4, 'name' => 'head teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $head = $this->makeUser($this->riverside, 4, 'Riverside Head');
        $otherHead = $this->makeUser($this->other, 4, 'Other Head');
        $teacher = $this->makeUser($this->riverside, 5, 'Riverside Teacher');

        $this->actingAs($teacher)->get('/admin/settings/exam-types')->assertNotFound();
        $this->actingAs($head)->get('/admin/settings/generalsettings')->assertNotFound();

        $this->actingAs($head)
            ->post('/admin/settings/exam-types', [
                'contributes' => [$this->eot->id => '1', $this->bot->id => '0'],
                'include' => [$this->eot->id => '1', $this->bot->id => '1'],
                'weight' => [$this->eot->id => '70', $this->bot->id => '30'],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $this->assertEquals(70.0, $this->riverside->fresh()->report_composition[(string) $this->eot->id]['weight']);
        $this->assertEquals(30.0, $this->riverside->fresh()->report_composition[(string) $this->bot->id]['weight']);
        $this->assertNull($this->other->fresh()->report_composition);

        $this->actingAs($otherHead)
            ->post('/admin/settings/exam-types', [
                'include' => [$this->eot->id => '1', $this->bot->id => '0'],
                'weight' => [$this->eot->id => '100', $this->bot->id => ''],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $this->assertEquals(70.0, $this->riverside->fresh()->report_composition[(string) $this->eot->id]['weight']);
        $this->assertTrue($this->other->fresh()->report_composition[(string) $this->eot->id]['include']);
        $this->assertEquals(100.0, $this->other->fresh()->report_composition[(string) $this->eot->id]['weight']);
        $this->assertFalse($this->other->fresh()->report_composition[(string) $this->bot->id]['include']);
    }

    public function test_another_schools_type_cannot_be_toggled(): void
    {
        $theirs = ExamType::create([
            'school_id' => $this->other->id, 'name' => 'Other Mock', 'code' => 'OM', 'contributes_to_report_total' => false,
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/settings/exam-types', [
                'contributes' => [$theirs->id => '1', $this->eot->id => '1'],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $prefs = $this->riverside->fresh()->exam_type_preferences;
        $this->assertArrayNotHasKey((string) $theirs->id, $prefs);
        $this->assertSame('1', $prefs[(string) $this->eot->id]);
    }

    private function makeUser(School $school, int $group, string $name): User
    {
        $user = User::create([
            'school_id' => $school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@test.ug',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $school->id, 'user_id' => $user->id, 'usergroup_id' => $group,
            'firstname' => $name, 'lastname' => 'User',
        ]);

        return $user;
    }
}
