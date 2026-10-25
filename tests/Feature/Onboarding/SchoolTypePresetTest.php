<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Country\CountryProfiles;
use App\Onboarding\Country\SchoolTypePreset;
use App\Onboarding\Steps\OnboardingStepAwaitingInput;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\Steps\StandardsStep;
use App\Onboarding\Steps\Steps\SubjectsStep;
use App\Onboarding\Steps\Steps\TermsStep;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\ClassStructureService;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\Onboarding\SchoolTypePresetFixtures;
use Tests\TestCase;

/**
 * A school-type preset is saved on the wizard's first run, when the school
 * has no classes or streams, or after the admin confirms a preview.
 * Continue and Use this do not put a removed class or stream back.
 * Adjust saves nothing. A type with no preset keeps the old defaults.
 */
class SchoolTypePresetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);
    }

    public function test_uganda_presets_follow_uneb_classes_and_a_level_streams(): void
    {
        $this->assertSame(SchoolTypePresetFixtures::CATEGORIES, array_keys(SchoolCategorySeeder::CATEGORIES));

        foreach (SchoolTypePresetFixtures::CATEGORIES as $category) {
            $school = $this->school('Uganda', $category);
            $preset = SchoolTypePreset::forSchool($school);
            $this->assertNotNull($preset, $category);
            $this->assertSame(SchoolCategorySeeder::expectedSectionNames($category), $preset->classNames(), $category);
            $this->assertSame(
                SchoolTypePreset::termsFor('Uganda', $category, 2031),
                CountryProfiles::forCountry('Uganda')->defaultTerms(2031),
                $category,
            );

            foreach ($preset->classes as $class) {
                $level = $this->levelFor($class['name']);
                $expectedStreams = $level === 'a-level' ? SchoolTypePresetFixtures::A_LEVEL_STREAMS : [];
                $this->assertSame($expectedStreams, $class['streams'], $category.' '.$class['name']);
                $this->assertSame(
                    SchoolCategorySeeder::subjectNamesForLevel($level),
                    $preset->subjectsAnswer()[$class['name']],
                    $category.' '.$class['name'],
                );
            }

            $schema = app(StepRegistry::class)->byKey('standards')->schema($school);
            $this->assertSame($preset->classes[0]['name'], $schema->defaults['class_name'], $category);
            $this->assertSame($preset->streamDefaultText(), $schema->defaults['stream'], $category);
            $this->assertSame('use_this', $schema->defaults['recommended_setup'], $category);
            $this->assertSame('use_this', app(StepRegistry::class)->byKey('terms')->schema($school)->defaults['recommended_setup']);
        }

        $blank = $this->school('Uganda', null);
        $standards = app(StepRegistry::class)->byKey('standards')->schema($blank);
        $this->assertSame('P1', $standards->defaults['class_name']);
        $this->assertSame('', $standards->defaults['stream']);
        $this->assertSame('', $standards->defaults['recommended_setup']);
        $this->assertSame('Mathematics', app(StepRegistry::class)->byKey('subjects')->schema($blank)->defaults['subject_name']);
    }

    public function test_other_countries_and_unknown_types_keep_the_old_defaults(): void
    {
        foreach (['Kenya', 'India', 'Brazil', null] as $country) {
            foreach ([null, 'nursery', 'o_a_level', 'vocational'] as $category) {
                $school = $this->school(is_string($country) ? $country : null, $category);
                $this->assertNull(SchoolTypePreset::forSchool($school), (string) $country.' '.$category);
                $schema = app(StepRegistry::class)->byKey('standards')->schema($school);
                $this->assertSame('P1', $schema->defaults['class_name']);
                $this->assertSame('', $schema->defaults['stream']);
                $this->assertSame('', $schema->defaults['recommended_setup']);
            }
        }
    }

    public function test_reading_the_preset_does_not_save(): void
    {
        $school = $this->school('Uganda', 'o_a_level');
        $this->year($school);
        $before = Section::where('school_id', $school->id)->count();

        $preset = SchoolTypePreset::forSchool($school);
        $this->assertNotNull($preset);
        $preset->standardsAnswer();
        $preset->subjectsAnswer();
        $preset->suggestion('standards', CountryProfiles::forCountry('Uganda'), 2027);
        foreach (app(StepRegistry::class)->all() as $step) {
            $step->schema($school);
        }

        $this->assertSame($before, Section::where('school_id', $school->id)->count());
        $this->assertSame(0, AcademicTerm::where('school_id', $school->id)->count());
        $this->assertStringNotContainsString('exam', strtolower($preset->suggestion('standards', CountryProfiles::forCountry('Uganda'), 2027)));
    }

    public function test_use_this_matches_typing_the_preset_and_adjust_saves_nothing(): void
    {
        $typed = $this->readySchool('Uganda', 'o_a_level', 'typed');
        $accepted = $this->readySchool('Uganda', 'o_a_level', 'accepted');
        $preset = SchoolTypePreset::forSchool($typed);
        $this->assertNotNull($preset);

        $before = Section::where('school_id', $accepted->id)->count();
        try {
            app(StandardsStep::class)->save($accepted, 'adjust');
            $this->fail('Adjust should wait for a list.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertStringContainsString('Nothing is saved yet', $e->getMessage());
        }
        $this->assertSame($before, Section::where('school_id', $accepted->id)->count());
        $this->assertFalse(Section::where('school_id', $accepted->id)->where('name', 'like', '% PCM')->exists());

        app(StandardsStep::class)->save($typed, $preset->standardsAnswer());
        app(SubjectsStep::class)->save($typed->fresh(), $preset->subjectsAnswer());
        app(TermsStep::class)->save($typed->fresh(), $preset->terms(CountryProfiles::forCountry('Uganda'), 2027));

        $driver = app(ToshiOnboardingV2Driver::class);
        $adminId = (int) User::where('school_id', $accepted->id)->value('id');
        $prompt = $driver->promptNext($accepted->fresh(), $adminId);
        $this->assertSame('standards', $prompt['key']);
        $this->assertSame(SchoolTypePreset::actions(), $prompt['options']);
        $this->assertStringContainsString("Here's the usual setup for an O-Level + A-Level school.", $prompt['question']);
        $this->assertStringContainsString('PCM, BCM, HEG, MEG', $prompt['question']);

        $preview = $driver->handleReply($accepted->fresh(), 'use_this', $adminId);
        $this->assertSame('awaiting', $preview['status'] ?? null);
        $this->assertStringContainsString('Nothing is saved yet', (string) ($preview['question'] ?? ''));
        $this->assertFalse(Section::where('school_id', $accepted->id)->where('name', 'like', '% PCM')->exists());
        $applied = $driver->handleReply($accepted->fresh(), 'apply', $adminId);
        $this->assertNotSame('rejected', $applied['status'] ?? null, (string) ($applied['hint'] ?? ''));
        $accepted->refresh();
        $this->assertSame('subjects', $driver->promptNext($accepted, $adminId)['key']);
        $this->assertNotSame('rejected', $driver->handleReply($accepted, 'Use this', $adminId)['status'] ?? null);
        OnboardingStepsService::markStepSkipped($accepted->fresh(), 'teachers');
        OnboardingStepsService::markStepSkipped($accepted->fresh(), 'students');
        $accepted->refresh();
        $termsPrompt = $driver->promptNext($accepted, $adminId);
        $this->assertSame('terms', $termsPrompt['key']);
        $this->assertStringContainsString('Term 1 (2027-02-03 to 2027-05-02)', $termsPrompt['question']);
        $this->assertNotSame('rejected', $driver->handleReply($accepted, 'use_this', $adminId)['status'] ?? null);

        $this->assertSame($this->structure($typed->fresh()), $this->structure($accepted->fresh()));

        $custom = $this->readySchool('Uganda', 'primary', 'custom');
        app(TermsStep::class)->save($custom, [[
            'name' => 'First term',
            'start' => '2027-01-10',
            'end' => '2027-04-10',
            'status' => 'current',
        ]]);
        $this->assertSame(['First term'], AcademicTerm::where('school_id', $custom->id)->orderBy('id')->pluck('name')->all());

        $kenya = $this->readySchool('Kenya', 'o_a_level', 'kenya');
        $kenyaPrompt = $driver->promptNext($kenya, (int) User::where('school_id', $kenya->id)->value('id'));
        $this->assertSame('standards', $kenyaPrompt['key']);
        $this->assertSame([], $kenyaPrompt['options']);
        $this->assertStringNotContainsString('PCM', $kenyaPrompt['question']);
        app(StandardsStep::class)->save($kenya, 'use_this');
        $this->assertFalse(Section::where('school_id', $kenya->id)->where('name', 'like', '% PCM')->exists());
    }

    public function test_wizard_continue_does_not_save_suggested_streams(): void
    {
        $admin = $this->adminFor($this->readySchool('Uganda', 'o_a_level', 'wizard'));
        $this->actingAs($admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));

        foreach ($component->get('structureStreamDrafts') as $draft) {
            $this->assertSame('', $draft);
        }
        $this->assertSame([''], $this->draftsFor($component, 'Senior One'));
        $component->assertSee('Apply suggested structure');
        $component->assertDontSee('Suggested streams are filled in');
        $this->assertFalse(Section::where('school_id', $admin->school_id)->where('name', 'Senior Five PCM')->exists());
        $before = Section::where('school_id', $admin->school_id)->count();

        $component->call('next');
        $this->assertSame('', $component->get('errorMessage'));
        $this->assertSame($before, Section::where('school_id', $admin->school_id)->count());
        $this->assertFalse(Section::where('school_id', $admin->school_id)->where('name', 'like', '% PCM')->exists());
    }

    public function test_wizard_can_change_prefilled_streams_and_terms(): void
    {
        $school = $this->readySchool('Uganda', 'o_a_level', 'edit');
        $admin = $this->adminFor($school);
        $this->actingAs($admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));

        $five = $this->sectionId($component, 'Senior Five');
        $six = $this->sectionId($component, 'Senior Six');
        $drafts = $component->get('structureStreamDrafts');
        $drafts[(string) $five] = 'Science';
        $drafts[(string) $six] = '';
        foreach ($drafts as $key => $value) {
            if (! in_array((string) $key, [(string) $five, (string) $six], true) && $value !== '') {
                $drafts[$key] = '';
            }
        }
        $component->set('structureStreamDrafts', $drafts)->call('next');
        $this->assertSame('', $component->get('errorMessage'));
        $this->assertTrue(Section::where('school_id', $school->id)->where('name', 'Senior Five Science')->exists());
        $this->assertFalse(Section::where('school_id', $school->id)->where('name', 'Senior Five PCM')->exists());
        $this->assertFalse(Section::where('school_id', $school->id)->where('name', 'Senior Six PCM')->exists());

        $component->call('goToStep', $this->stepIndex($component, 'terms'));
        $component
            ->set('termDrafts', [[
                'name' => 'First term',
                'start' => '2027-01-10',
                'end' => '2027-04-10',
            ]])
            ->set('currentTermName', 'First term')
            ->call('next');
        $this->assertSame('', $component->get('errorMessage'));
        $this->assertSame(
            ['First term'],
            AcademicTerm::where('school_id', $school->id)->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_primary_preset_does_not_invent_streams(): void
    {
        $school = $this->readySchool('Uganda', 'primary', 'primary');
        $admin = $this->adminFor($school);
        $this->actingAs($admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));

        foreach ($component->get('structureStreamDrafts') as $draft) {
            $this->assertSame('', $draft);
        }
        $component->assertSee('No streams yet');
        $before = Section::where('school_id', $school->id)->count();
        $component->call('next');
        $this->assertSame($before, Section::where('school_id', $school->id)->count());
        $this->assertSame('', $component->get('subjectName'));
    }

    public function test_reopening_after_clearing_suggestions_does_not_create_classes(): void
    {
        $school = $this->readySchool('Uganda', 'o_a_level', 'cleared');
        $admin = $this->adminFor($school);
        $this->actingAs($admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));
        $cleared = $component->get('structureStreamDrafts');
        foreach ($cleared as $key => $value) {
            $cleared[$key] = '';
        }
        $component->set('structureStreamDrafts', $cleared)->call('next');
        $this->assertSame('', $component->get('errorMessage'));

        $before = $this->sectionNames($school);
        $this->assertNotContains('Senior Five PCM', $before);
        $this->assertNotContains('Senior Six PCM', $before);

        $reopened = Livewire::test(ManualOnboardingWizard::class);
        $reopened->call('goToStep', $this->stepIndex($reopened, 'standards'));
        foreach ($reopened->get('structureStreamDrafts') as $draft) {
            $this->assertSame('', $draft);
        }
        $reopened->assertDontSee('Suggested streams are filled in');
        $reopened->call('next');
        $this->assertSame('', $reopened->get('errorMessage'));
        $this->assertSame($before, $this->sectionNames($school));

        try {
            app(StandardsStep::class)->save($school->fresh(), 'use_this');
            $this->fail('Use this must show the preview and save nothing.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertStringContainsString('Nothing is saved yet', $e->getMessage());
        }
        $this->assertSame($before, $this->sectionNames($school));
    }

    public function test_reopening_after_a_custom_stream_does_not_create_classes(): void
    {
        $school = $this->readySchool('Uganda', 'o_a_level', 'arts');
        $admin = $this->adminFor($school);
        $this->actingAs($admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));
        $five = $this->sectionId($component, 'Senior Five');
        $drafts = $component->get('structureStreamDrafts');
        foreach ($drafts as $key => $value) {
            $drafts[$key] = (string) $key === (string) $five ? 'Arts' : '';
        }
        $component->set('structureStreamDrafts', $drafts)->call('next');
        $this->assertSame('', $component->get('errorMessage'));

        $before = $this->sectionNames($school);
        $this->assertContains('Senior Five Arts', $before);
        $this->assertNotContains('Senior Five PCM', $before);
        $this->assertNotContains('Senior Six PCM', $before);

        $reopened = Livewire::test(ManualOnboardingWizard::class);
        $reopened->call('goToStep', $this->stepIndex($reopened, 'standards'));
        $reopened->assertSee('Arts');
        foreach ($reopened->get('structureStreamDrafts') as $draft) {
            $this->assertSame('', $draft);
        }
        $count = count($before);
        $reopened->call('next');
        $this->assertSame('', $reopened->get('errorMessage'));
        $after = $this->sectionNames($school);
        $this->assertSame($before, $after);
        $this->assertCount($count, $after);
        $this->assertGreaterThan(6, $count);
        $this->assertLessThan(15, $count);

        try {
            app(StandardsStep::class)->save($school->fresh(), 'use_this');
            $this->fail('Use this must show the preview and save nothing.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertStringContainsString('Nothing is saved yet', $e->getMessage());
        }
        $this->assertSame($before, $this->sectionNames($school));
    }

    public function test_use_this_does_not_add_streams_to_a_class_that_already_has_some(): void
    {
        $school = $this->readySchool('Uganda', 'o_a_level', 'partial');
        $year = AcademicYear::where('school_id', $school->id)->first();
        $base = Section::where('school_id', $school->id)->where('name', 'Senior Five')->first();
        $this->assertNotNull($year);
        $this->assertNotNull($base);
        app(OnboardingEngine::class)->addClassStream($school, $year, $base, 'Arts');

        $before = $this->sectionNames($school);
        try {
            app(StandardsStep::class)->save($school->fresh(), 'use_this');
            $this->fail('Use this must show the preview and save nothing.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertStringContainsString('Nothing is saved yet', $e->getMessage());
            $this->assertStringContainsString('Senior Six PCM', $e->getMessage());
        }
        $this->assertSame($before, $this->sectionNames($school));
        $this->assertNotContains('Senior Six PCM', $this->sectionNames($school));
    }

    public function test_sunset_ps_continue_does_not_restore_cleared_a_level_streams(): void
    {
        [$school] = $this->sunsetSchool();
        $this->clearALevelStreams($school);
        $admin = $this->adminFor($school);
        $this->actingAs($admin);

        $before = $this->sectionRows($school);
        $this->assertNotContains('Senior Five PCM|1', $before);
        $this->assertTrue(
            Section::query()->where('school_id', $school->id)->where('name', 'Senior Five')->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })->exists()
        );

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));
        foreach ($component->get('structureStreamDrafts') as $draft) {
            $this->assertSame('', $draft);
        }
        $component->call('next');
        $this->assertSame('', (string) $component->get('errorMessage'));
        $this->assertSame($before, $this->sectionRows($school->fresh()));
    }

    public function test_first_run_continue_applies_the_preset_when_the_school_has_no_structure(): void
    {
        $school = $this->school('Uganda', 'o_a_level');
        $school->forceFill([
            'name' => 'Sunset PS First Run',
            'student_size' => 'Up to 500',
            'curriculum' => 'uneb',
            'ministry_code' => 'SUNSET1',
        ])->save();
        $this->year($school);
        $this->actingAs($this->adminFor($school));

        $this->assertSame(0, Section::where('school_id', $school->id)->count());
        $plan = app(ClassStructureService::class)->suggestedStructurePlan($school);
        $this->assertContains('Senior Five', $plan['items']);
        $this->assertContains('Senior Five PCM', $plan['items']);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));
        $this->assertSame(0, Section::where('school_id', $school->id)->count());
        $component->call('next');
        $this->assertSame('', (string) $component->get('errorMessage'));
        $this->assertEqualsCanonicalizing($plan['items'], $this->activeSectionNames($school->fresh()));
    }

    public function test_apply_suggested_structure_previews_then_adds_exactly_those_items(): void
    {
        [$school] = $this->sunsetSchool();
        $this->clearALevelStreams($school);
        $this->actingAs($this->adminFor($school));

        $beforeActive = $this->activeSectionNames($school);
        $beforeRows = $this->sectionRows($school);
        $expected = [];
        foreach (['Senior Five', 'Senior Six'] as $className) {
            foreach (SchoolTypePreset::A_LEVEL_STREAMS as $label) {
                $expected[] = $className.' '.$label;
            }
        }

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component->call('goToStep', $this->stepIndex($component, 'standards'));
        $component->call('previewSuggestedStructure');
        $this->assertSame($beforeRows, $this->sectionRows($school->fresh()));
        $this->assertSame($expected, $component->get('structurePresetPreview'));
        $component->assertSee('Nothing has been added yet');

        $component->call('confirmSuggestedStructure');
        $this->assertSame('', (string) $component->get('errorMessage'));
        $added = array_values(array_diff($this->activeSectionNames($school->fresh()), $beforeActive));
        sort($added);
        $sortedPreview = $expected;
        sort($sortedPreview);
        $this->assertSame($sortedPreview, $added);

        $toshi = $this->sunsetSchool('Sunset PS Toshi')[0];
        $this->clearALevelStreams($toshi);
        $toshiBefore = $this->activeSectionNames($toshi);
        try {
            app(StandardsStep::class)->save($toshi->fresh(), 'use_this');
            $this->fail('Use this must show the preview and save nothing.');
        } catch (OnboardingStepAwaitingInput $e) {
            $this->assertStringContainsString('Nothing is saved yet', $e->getMessage());
            foreach ($expected as $item) {
                $this->assertStringContainsString($item, $e->getMessage());
            }
        }
        $this->assertSame($toshiBefore, $this->activeSectionNames($toshi->fresh()));

        app(StandardsStep::class)->save($toshi->fresh(), 'apply');
        $toshiAdded = array_values(array_diff($this->activeSectionNames($toshi->fresh()), $toshiBefore));
        sort($toshiAdded);
        $this->assertSame($sortedPreview, $toshiAdded);
    }

    /**
     * @return list<string>
     */
    private function sectionNames(School $school): array
    {
        return Section::where('school_id', $school->id)->orderBy('name')->pluck('name')->all();
    }

    /**
     * @return array{0: School, 1: AcademicYear}
     */
    private function sunsetSchool(string $name = 'Sunset PS'): array
    {
        $school = $this->readySchool('Uganda', 'o_a_level', 'sunset-'.uniqid());
        $school->forceFill(['name' => $name])->save();
        $year = AcademicYear::where('school_id', $school->id)->first();
        $this->assertNotNull($year);
        $service = app(ClassStructureService::class);
        foreach (['Senior Five', 'Senior Six'] as $className) {
            $base = Section::query()->where('school_id', $school->id)->where('name', $className)->first();
            $this->assertNotNull($base);
            foreach (SchoolTypePreset::A_LEVEL_STREAMS as $label) {
                $service->addStream($school, $year, $base, $label);
            }
        }

        return [$school->fresh(), $year];
    }

    private function clearALevelStreams(School $school): void
    {
        $ids = Section::query()
            ->where('school_id', $school->id)
            ->where(function ($query) {
                $query->where('name', 'like', 'Senior Five %')
                    ->orWhere('name', 'like', 'Senior Six %');
            })
            ->pluck('id');

        Section::query()->where('school_id', $school->id)->whereIn('id', $ids)->update(['status' => '0']);
        StandardLink::query()->where('school_id', $school->id)->whereIn('section_id', $ids)->update(['status' => '0']);
    }

    /**
     * @return list<string>
     */
    private function sectionRows(School $school): array
    {
        return Section::query()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['name', 'status'])
            ->map(fn (Section $section): string => $section->name.'|'.(string) $section->status)
            ->all();
    }

    /**
     * @return list<string>
     */
    private function activeSectionNames(School $school): array
    {
        return Section::query()
            ->where('school_id', $school->id)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function school(?string $country, ?string $category): School
    {
        $uid = uniqid();

        return School::create([
            'name' => 'Preset School '.$uid,
            'email' => "preset-{$uid}@test.sch.ug",
            'phone' => '07'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'slug' => 'preset-school-'.$uid,
            'status' => 1,
            'registration_country' => $country,
            'school_category' => $category,
            'toshi_enabled' => 0,
            'toshi_mode' => 'preview',
        ]);
    }

    private function readySchool(string $country, string $category, string $label): School
    {
        $school = $this->school($country, $category);
        $school->forceFill([
            'name' => 'Preset '.$label.' School',
            'student_size' => 'Up to 500',
            'curriculum' => 'uneb',
            'ministry_code' => strtoupper(substr($label, 0, 8)).random_int(100, 999),
            'uneb_center_number' => '',
        ])->save();
        $this->year($school);
        SchoolCategorySeeder::seed($school->fresh());
        $this->adminFor($school);

        return $school->fresh();
    }

    private function adminFor(School $school): User
    {
        $existing = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();
        if ($existing) {
            return $existing;
        }

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Preset Admin',
            'email' => 'admin-'.$school->id.'@preset.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Preset',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return $admin;
    }

    private function year(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-01 00:00:00',
            'end_date' => '2027-12-31 23:59:59',
            'status' => 1,
        ]);
    }

    private function levelFor(string $className): string
    {
        foreach (['nursery', 'primary', 'o-level', 'a-level'] as $level) {
            if (in_array($className, SchoolCategorySeeder::sectionNamesForLevel($level), true)) {
                return $level;
            }
        }

        $this->fail('No level for '.$className);
    }

    /**
     * @return list<array{name: string, streams: list<string>, subjects: list<string>}>
     */
    private function structure(School $school): array
    {
        $classes = Section::where('school_id', $school->id)->orderBy('name')->pluck('name')->all();
        $subjects = \App\Models\Subject::where('school_id', $school->id)
            ->get()
            ->map(function ($subject) use ($school): string {
                $section = Section::query()
                    ->where('school_id', $school->id)
                    ->whereKey($subject->section_id)
                    ->value('name');

                return $subject->getRawOriginal('name').'@'.$section;
            })
            ->sort()
            ->values()
            ->all();
        $terms = AcademicTerm::where('school_id', $school->id)->orderBy('name')->get()
            ->map(fn (AcademicTerm $term): string => $term->name.'|'.optional($term->starts_on)?->toDateString().'|'.$term->status)
            ->all();

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'terms' => $terms,
        ];
    }

    private function stepIndex(object $component, string $key): int
    {
        foreach ($component->get('steps') as $index => $step) {
            if (($step['key'] ?? '') === $key) {
                return (int) $index;
            }
        }

        $this->fail('No step '.$key);
    }

    private function sectionId(object $component, string $className): int
    {
        foreach ($component->get('structureClasses') as $row) {
            if (strcasecmp((string) ($row['name'] ?? ''), $className) === 0) {
                return (int) $row['section_id'];
            }
        }

        $this->fail('No class '.$className);
    }

    /**
     * @return list<string>
     */
    private function draftsFor(object $component, string $className): array
    {
        $id = (string) $this->sectionId($component, $className);

        return [(string) ($component->get('structureStreamDrafts')[$id] ?? '')];
    }
}
