<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Onboarding\OnboardingProgress;
use App\Onboarding\Steps\StepSchemaCatalog;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingProgressTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private ?string $playbookFixture = null;

    /** @var list<array<string, mixed>> */
    private array $summaryWarnings = [];

    protected function setUp(): void
    {
        OnboardingProgress::setPlaybookPath(null);
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        [$this->school, $this->admin] = $this->makeSchool("Ada's School", 'ada@progress.test');
    }

    public function test_summary_lines_come_from_the_playbook_and_match_the_step_count(): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();
        OnboardingStepsService::markStepSkipped($this->school, 'teachers');
        OnboardingStepsService::markStepSkipped($this->school, 'students');

        $progress = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $legacy = OnboardingStepsService::progress($this->school->fresh(), $this->admin->id);

        $this->assertSame(['done', 'total', 'current_key', 'summary'], array_keys($progress));
        $this->assertSame($legacy['done'], $progress['done']);
        $this->assertSame($legacy['total'], $progress['total']);
        $this->assertSame($legacy['next']['key'] ?? null, $progress['current_key']);
        $this->assertCount($progress['done'], $progress['summary']);
        $this->assertSame('student_size', $progress['current_key']);
        $this->assertSame('School name: {name}', OnboardingProgress::template('school_name'));
        $this->assertSame('Classes: {classes}, {streams}', OnboardingProgress::template('standards'));
        $this->assertContains('School name: Kisubi Primary School', $progress['summary']);
        $this->assertContains('Teachers: '.OnboardingProgress::SKIPPED_FOR_LATER, $progress['summary']);
        $this->assertContains('Students: '.OnboardingProgress::SKIPPED_FOR_LATER, $progress['summary']);

        $blocking = array_column(
            OnboardingStepsService::blockingIncompleteSteps($this->school->fresh(), $this->admin->id),
            'key',
        );
        $this->assertNotContains('teachers', $blocking);
        $this->assertNotContains('students', $blocking);
        $this->assertNotContains('whatsapp_verify', $blocking);

        foreach (StepSchemaCatalog::EXCLUDED_KEYS as $banned) {
            $this->assertArrayNotHasKey($banned, OnboardingProgress::summaryTokens());
        }
    }

    public function test_whatsapp_line_is_left_out_until_the_code_matches(): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();

        $before = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        foreach ($before['summary'] as $line) {
            $this->assertStringStartsNotWith('WhatsApp:', $line);
        }
        $this->assertFalse(OnboardingStepsService::isStepComplete('whatsapp_verify', $this->school->fresh(), $this->admin->id));

        WhatsAppUser::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'phone' => '+256700000301',
            'verified_at' => null,
            'opted_in' => true,
        ]);

        $unverified = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        foreach ($unverified['summary'] as $line) {
            $this->assertStringStartsNotWith('WhatsApp:', $line);
        }

        WhatsAppUser::query()->where('user_id', $this->admin->id)->update(['verified_at' => now()]);

        $after = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $this->assertContains('WhatsApp: +256700000301', $after['summary']);
        $this->assertSame(
            OnboardingStepsService::progress($this->school->fresh(), $this->admin->id)['done'],
            $after['done'],
        );
    }

    public function test_summary_stays_inside_the_school(): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();
        [$other] = $this->makeSchool('Hilltop Secondary School', 'hilltop@progress.test');

        $kisubi = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $hilltop = OnboardingProgress::for($other->fresh(), (int) User::query()->where('school_id', $other->id)->value('id'));

        $this->assertContains('School name: Kisubi Primary School', $kisubi['summary']);
        $this->assertNotContains('School name: Hilltop Secondary School', $kisubi['summary']);
        $this->assertContains('School name: Hilltop Secondary School', $hilltop['summary']);
        $this->assertNotContains('School name: Kisubi Primary School', $hilltop['summary']);
    }

    public function test_wizard_sidebar_shows_the_running_summary(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->assertSeeHtml('data-testid="wizard-running-summary"')
            ->assertSee('Nothing saved yet.')
            ->assertSee('Now: School name');

        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();

        Livewire::test(ManualOnboardingWizard::class)
            ->assertSee('School name: Kisubi Primary School')
            ->assertSee('Now: Approximate school size');
    }

    public function test_dashboard_banner_lists_finished_steps(): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();

        $this->actingAs($this->admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('data-testid="dashboard-v2-setup-summary"', false)
            ->assertSee('School name: Kisubi Primary School', false);
    }

    public function test_toshi_dock_has_the_summary_bar_and_the_modal_list_does_not(): void
    {
        $this->school->forceFill([
            'name' => 'Kisubi Primary School',
            'toshi_mode' => 'onboarding',
            'toshi_enabled' => 0,
        ])->save();

        $html = Livewire::actingAs($this->admin)
            ->test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->html();

        $progress = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $this->assertSame(1, substr_count($html, 'data-testid="toshi-running-summary"'));
        $this->assertStringContainsString('Setup '.$progress['done'].' of '.$progress['total'], $html);
        $this->assertStringContainsString('School name: Kisubi Primary School', $html);
        $this->assertStringContainsString('data-testid="toshi-setup-list-modal"', $html);
    }

    public function test_dashboard_banner_survives_a_missing_playbook(): void
    {
        $this->assertSurfaceSurvives('dashboard', 'missing_file');
    }

    public function test_dashboard_banner_survives_a_missing_summary_line(): void
    {
        $this->assertSurfaceSurvives('dashboard', 'missing_line');
    }

    public function test_wizard_sidebar_survives_a_missing_playbook(): void
    {
        $this->assertSurfaceSurvives('wizard', 'missing_file');
    }

    public function test_wizard_sidebar_survives_a_missing_summary_line(): void
    {
        $this->assertSurfaceSurvives('wizard', 'missing_line');
    }

    public function test_toshi_dock_survives_a_missing_playbook(): void
    {
        $this->assertSurfaceSurvives('toshi', 'missing_file');
    }

    public function test_toshi_dock_survives_a_missing_summary_line(): void
    {
        $this->assertSurfaceSurvives('toshi', 'missing_line');
    }

    public function test_parsed_playbook_is_not_read_again_in_the_same_process(): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();
        OnboardingProgress::setPlaybookPath(null);
        $before = OnboardingProgress::playbookReads();

        $first = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $second = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        OnboardingProgress::template('school_name');

        $this->assertSame($before + 1, OnboardingProgress::playbookReads());
        $this->assertSame($first['summary'], $second['summary']);
        $this->assertContains('School name: Kisubi Primary School', $second['summary']);
    }

    public function test_running_summary_is_not_added_outside_the_three_surfaces(): void
    {
        $wizard = file_get_contents(resource_path('views/livewire/manual-onboarding-wizard.blade.php'));
        $dock = file_get_contents(resource_path('views/livewire/partials/toshi-setup-checklist.blade.php'));
        $dashboard = file_get_contents(app_path('Services/DashboardV2DataService.php'));
        $chip = file_get_contents(resource_path('views/layouts/partials/dashboard-v2-chip.blade.php'));
        $pill = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));

        $this->assertIsString($wizard);
        $this->assertIsString($dock);
        $this->assertIsString($dashboard);
        $this->assertStringContainsString('OnboardingProgress', $wizard);
        $this->assertStringContainsString('OnboardingProgress', $dock);
        $this->assertStringContainsString('OnboardingProgress', $dashboard);
        $this->assertStringNotContainsString('OnboardingProgress', (string) $chip);
        $this->assertStringNotContainsString('OnboardingProgress', (string) $pill);
    }

    protected function tearDown(): void
    {
        OnboardingProgress::setPlaybookPath(null);
        if ($this->playbookFixture !== null && is_file($this->playbookFixture)) {
            unlink($this->playbookFixture);
        }
        $this->playbookFixture = null;

        parent::tearDown();
    }

    private function assertSurfaceSurvives(string $surface, string $kind): void
    {
        $this->school->forceFill(['name' => 'Kisubi Primary School'])->save();
        if ($surface === 'toshi') {
            $this->school->forceFill([
                'toshi_mode' => 'onboarding',
                'toshi_enabled' => 0,
            ])->save();
        }

        $this->usePlaybook($kind);
        $this->listenForSummaryWarnings();

        $running = OnboardingProgress::for($this->school->fresh(), $this->admin->id);
        $this->assertSame([], $running['summary']);
        $this->assertGreaterThan(0, $running['done']);
        $count = 'Setup '.$running['done'].' of '.$running['total'];

        if ($surface === 'dashboard') {
            $this->actingAs($this->admin)
                ->get('/admin/dashboard')
                ->assertOk()
                ->assertSee($count.' done', false)
                ->assertDontSee('data-testid="dashboard-v2-setup-summary"', false)
                ->assertDontSee('School name: Kisubi Primary School', false);
        } elseif ($surface === 'wizard') {
            $this->actingAs($this->admin);
            Livewire::test(ManualOnboardingWizard::class)
                ->assertSeeHtml('data-testid="wizard-running-summary"')
                ->assertSee($count)
                ->assertSee('Nothing saved yet.')
                ->assertDontSee('School name: Kisubi Primary School');
        } else {
            $html = Livewire::actingAs($this->admin)
                ->test(AgentToshi::class)
                ->set('mode', 'complete')
                ->set('schoolId', $this->school->id)
                ->html();

            $this->assertStringContainsString('data-testid="toshi-running-summary"', $html);
            $this->assertStringContainsString($count, $html);
            $this->assertStringContainsString('Nothing saved yet.', $html);
            $this->assertStringNotContainsString('School name: Kisubi Primary School', $html);
        }

        $this->assertSame(
            [['reason' => $kind] + ($kind === 'missing_line' ? ['step' => 'school_name'] : [])],
            $this->summaryWarnings,
        );
        $encoded = (string) json_encode($this->summaryWarnings);
        $this->assertStringNotContainsString('Kisubi', $encoded);
        $this->assertStringNotContainsString("Ada", $encoded);
        $this->assertStringNotContainsString('@', $encoded);
        $this->assertStringNotContainsString((string) $this->school->phone, $encoded);
    }

    private function usePlaybook(string $kind): void
    {
        if ($kind === 'missing_file') {
            OnboardingProgress::setPlaybookPath(sys_get_temp_dir().'/klassapp-playbook-missing-'.uniqid('', true).'.md');

            return;
        }

        $this->playbookFixture = tempnam(sys_get_temp_dir(), 'klassapp-playbook-');
        $this->assertIsString($this->playbookFixture);
        file_put_contents($this->playbookFixture, <<<'MD'
## step: school_name

Saved on the school. This step has no Summary line.

## step: student_size

Summary: School size: {size}
MD);
        OnboardingProgress::setPlaybookPath($this->playbookFixture);
    }

    private function listenForSummaryWarnings(): void
    {
        $this->summaryWarnings = [];
        Log::listen(function (MessageLogged $event): void {
            if ($event->level === 'warning' && $event->message === 'Onboarding running summary skipped.') {
                $this->summaryWarnings[] = $event->context;
            }
        });
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function makeSchool(string $name, string $email): array
    {
        $school = School::create([
            'name' => $name,
            'email' => $email,
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'progress-'.substr(md5($email), 0, 12),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => config('toshi.default_mode'),
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Progress Admin',
            'email' => 'admin-'.$email,
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Progress',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return [$school, $admin];
    }
}
