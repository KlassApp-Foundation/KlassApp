<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\EmisSchool;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\OnboardingStepsService;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Standing regression guard: wizard vs Toshi complete-mode must leave the same
 * normalized DB shape for equivalent onboarding inputs (Phase 1D-a).
 *
 * Drivers use public entry points only:
 * - ManualOnboardingWizard::next() chain ending in confirmReview()
 * - AgentToshi::confirmOnboarding() in complete mode
 */
class OnboardingEngineParityTest extends TestCase
{
    use RefreshDatabase;

    private Country $uganda;

    private Plan $freemium;

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

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        EmisSchool::create([
            'emis_code' => 'EMIS-PARITY',
            'school_name' => 'Parity EMIS School',
            'district' => 'Kampala',
            'status' => 1,
        ]);

        $this->freemium = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_scenario_a_simple_primary_wizard_matches_toshi_complete_mode(): void
    {
        $payload = [
            'school_name' => 'Parity Sunrise Academy',
            'ministry_code' => 'EMIS-PARITY-A',
            'category' => 'primary',
            'whatsapp' => '+256700111001',
            'fee_name' => 'Tuition',
            'fee_amount' => 100000.0,
            'term_name' => 'Term 1',
            'term_start' => now()->startOfYear()->toDateString(),
            'term_end' => now()->startOfYear()->addMonths(4)->toDateString(),
            'teachers' => [],
            'students' => [],
        ];

        $wizardSchool = $this->seedPlaceholderSchool('Parity A Wizard', 'a-wizard@parity.sch.ug', 'admin-a-w@parity.sch.ug', '+256700111010');
        $toshiSchool = $this->seedPlaceholderSchool('Parity A Toshi', 'a-toshi@parity.sch.ug', 'admin-a-t@parity.sch.ug', '+256700111011');

        $this->runWizardPath($wizardSchool['admin'], array_merge($payload, [
            'school_name' => 'Parity Sunrise Wizard',
            'ministry_code' => 'EMIS-PAR-AW',
            'whatsapp' => '+256700111001',
        ]));
        $this->runToshiCompletePath(
            $toshiSchool['admin'],
            $toshiSchool['school'],
            array_merge($payload, [
                'school_name' => 'Parity Sunrise Toshi',
                'ministry_code' => 'EMIS-PAR-AT',
            ]),
            '+256700111002'
        );

        $this->assertSame(
            $this->snapshot($wizardSchool['school']->fresh()),
            $this->snapshot($toshiSchool['school']->fresh()),
            'Scenario A: wizard and Toshi complete-mode DB snapshots must match'
        );
    }

    public function test_scenario_b_candidate_class_board_reg_wizard_matches_toshi_complete_mode(): void
    {
        $payload = [
            'school_name' => 'Parity Candidate Primary',
            'ministry_code' => 'EMIS-PARITY-B',
            'category' => 'primary',
            'whatsapp' => '+256700222001',
            'fee_name' => 'Tuition',
            'fee_amount' => 150000.0,
            'term_name' => 'Term 1',
            'term_start' => now()->startOfYear()->toDateString(),
            'term_end' => now()->startOfYear()->addMonths(4)->toDateString(),
            'teachers' => [],
            'students' => [
                [
                    'name' => 'Amina Candidate',
                    'class' => 'Primary Seven',
                    'school_student_id' => 'SCH-P7-001',
                    'board_registration_number' => 'U1234/567',
                ],
            ],
        ];

        $wizardSchool = $this->seedPlaceholderSchool('Parity B Wizard', 'b-wizard@parity.sch.ug', 'admin-b-w@parity.sch.ug', '+256700222010');
        $toshiSchool = $this->seedPlaceholderSchool('Parity B Toshi', 'b-toshi@parity.sch.ug', 'admin-b-t@parity.sch.ug', '+256700222011');

        $this->runWizardPath($wizardSchool['admin'], array_merge($payload, [
            'school_name' => 'Parity Candidate Wizard',
            'ministry_code' => 'EMIS-PAR-BW',
            'whatsapp' => '+256700222001',
        ]));
        $this->runToshiCompletePath(
            $toshiSchool['admin'],
            $toshiSchool['school'],
            array_merge($payload, [
                'school_name' => 'Parity Candidate Toshi',
                'ministry_code' => 'EMIS-PAR-BT',
            ]),
            '+256700222002'
        );

        $wizardSnap = $this->snapshot($wizardSchool['school']->fresh());
        $toshiSnap = $this->snapshot($toshiSchool['school']->fresh());

        $this->assertSame($wizardSnap, $toshiSnap, 'Scenario B: wizard and Toshi snapshots must match');

        $this->assertNotEmpty($wizardSnap['student_academics']);
        $this->assertSame('U1234/567', $wizardSnap['student_academics'][0]['board_registration_number']);
        $this->assertSame('Primary Seven', $wizardSnap['student_academics'][0]['section']);
    }

    /**
     * @return array{school: School, admin: User}
     */
    private function seedPlaceholderSchool(string $name, string $schoolEmail, string $adminEmail, string $phone): array
    {
        $school = School::create([
            'name' => $name,
            'email' => $schoolEmail,
            'phone' => $phone,
            'slug' => \Illuminate\Support\Str::slug($name),
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Parity Admin',
            'email' => $adminEmail,
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Parity',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return ['school' => $school, 'admin' => $admin];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function runWizardPath(User $admin, array $payload): void
    {
        $this->actingAs($admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', $payload['school_name'])
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', $payload['category'])
            ->call('next')
            ->set('ministryCode', $payload['ministry_code'])
            ->call('next')
            ->call('next') // uneb skip
            ->call('next'); // academic year → seeder fills classes/subjects

        // Optional teachers / students. Skipping teachers via next() jumps past other
        // optional steps (students) to the next blocking step — so when we need
        // students, land on that step explicitly.
        if ($payload['students'] !== []) {
            $studentIdx = null;
            foreach ($component->instance()->steps as $i => $step) {
                if (($step['key'] ?? '') === 'students') {
                    $studentIdx = $i;
                    break;
                }
            }
            $this->assertNotNull($studentIdx, 'students step missing from wizard');
            $component->call('goToStep', $studentIdx);

            foreach ($payload['students'] as $student) {
                $component
                    ->set('studentName', $student['name'])
                    ->set('studentClass', $student['class'])
                    ->set('studentSchoolStudentId', $student['school_student_id'] ?? '')
                    ->set('studentBoardRegNumber', $student['board_registration_number'] ?? '')
                    ->call('addStudentDraft');
            }
            $this->assertNotEmpty($component->get('studentDrafts'));
            $component->call('next');
            $this->assertSame('', $component->get('errorMessage'), 'Wizard students step error');
        } else {
            $component->call('next'); // skip teachers
            $component->call('next'); // skip students
        }

        $component
            ->set('termName', $payload['term_name'])
            ->set('termStartsOn', $payload['term_start'])
            ->set('termEndsOn', $payload['term_end'])
            ->call('next')
            // Empty class → school-wide fee (matches Toshi feesForEngine without class)
            ->set('className', '')
            ->set('feeName', $payload['fee_name'])
            ->set('feeAmount', (string) $payload['fee_amount'])
            ->call('next')
            ->set('whatsappPhone', $payload['whatsapp'])
            ->call('next')
            ->set('selectedPlanId', (int) $this->freemium->id)
            ->call('next'); // plan → review

        $instance = $component->instance();
        $this->assertSame('review', $instance->steps[$instance->stepIndex]['key'] ?? null);

        $component->call('confirmReview')->assertSet('finished', true);

        $this->assertFalse(
            OnboardingStepsService::hasBlockingIncompleteSteps($admin->school->fresh(), $admin->id)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function runToshiCompletePath(User $admin, School $school, array $payload, string $whatsappPhone): void
    {
        $this->actingAs($admin);

        // Identity + category via the same public APIs / engine paths Toshi uses
        // before commit (complete-mode commitAll does not call saveSchoolCategory).
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
            'type' => 'Current Academic Year',
        ]);

        $component = Livewire::test(AgentToshi::class);
        $component
            ->set('mode', 'complete')
            ->set('schoolId', $school->id)
            ->set('schoolName', $payload['school_name'])
            ->set('curriculum', 'uneb')
            ->set('schoolCountry', 'Uganda')
            ->set('ministryCode', $payload['ministry_code'])
            ->set('unebCenterNumber', '')
            ->set('academicYearLabel', (string) now()->year)
            ->call('selectSchoolCategory', $payload['category']);

        // Seeder already created canonical sections; do not re-pass custom standards
        // that would diverge from the wizard's early-return after seeding.
        $component
            ->set('standards', [])
            ->set('subjects', [])
            ->set('teacherList', [])
            ->set('teacherLinks', [])
            ->set('terms', [
                [
                    'name' => $payload['term_name'],
                    'start' => $payload['term_start'],
                    'end' => $payload['term_end'],
                ],
            ])
            ->set('actionData', [
                'fees' => [
                    [
                        'name' => $payload['fee_name'],
                        'amount' => $payload['fee_amount'],
                    ],
                ],
                'students' => $payload['students'],
            ])
            ->set('studentList', array_column($payload['students'], 'name'))
            ->set('whatsappPhone', $whatsappPhone)
            ->set('whatsappVerified', true)
            ->set('selectedPlanId', (int) $this->freemium->id);

        // selectSchoolCategory persistState() stores curriculumDefaults() class names
        // (Primary 1…). Clearing standards to [] would let resolveCollectedDataForCommit()
        // restore those from session and diverge from SchoolCategorySeeder (Primary One…).
        session()->forget('toshi_state');

        $component->call('confirmOnboarding');

        $this->assertTrue(
            (bool) data_get($component->get('reviewData'), 'committed'),
            'confirmOnboarding did not commit'
        );

        $this->assertFalse(
            OnboardingStepsService::hasBlockingIncompleteSteps($school->fresh(), $admin->id)
        );
    }

    /**
     * Normalized, comparable school-onboarding snapshot (volatile ids / emails / hashes omitted).
     *
     * @return array<string, mixed>
     */
    private function snapshot(School $school): array
    {
        $school->refresh();

        $sections = Section::where('school_id', $school->id)->orderBy('name')->pluck('name')->values()->all();
        $standards = Standard::where('school_id', $school->id)->orderBy('name')->pluck('name')->values()->all();

        $subjects = Subject::where('school_id', $school->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $s) => [
                // Subject accessor uppercases; use raw DB name for stability
                'name' => (string) DB::table('subjects')->where('id', $s->id)->value('name'),
                'section' => optional(Section::find($s->section_id))->name,
                'standard' => optional(Standard::find($s->standard_id))->name,
            ])
            ->sortBy(fn ($row) => $row['standard'].'|'.$row['section'].'|'.$row['name'])
            ->values()
            ->all();

        $terms = AcademicTerm::where('school_id', $school->id)
            ->orderBy('name')
            ->get()
            ->map(fn (AcademicTerm $t) => [
                'name' => $t->name,
                'start' => optional($t->starts_on)?->toDateString(),
                'end' => optional($t->ends_on)?->toDateString(),
            ])
            ->values()
            ->all();

        $fees = FeesCategories::where('school_id', $school->id)
            ->orderBy('name')
            ->orderBy('standard_id')
            ->get()
            ->map(fn (FeesCategories $f) => [
                'name' => $f->name,
                'amount' => (float) $f->amount,
                'standard' => optional(Standard::find($f->standard_id))->name,
                'section' => $f->section_id ? optional(Section::find($f->section_id))->name : null,
            ])
            ->sortBy(fn ($row) => $row['name'].'|'.$row['standard'].'|'.($row['section'] ?? ''))
            ->values()
            ->all();

        $usersByGroup = [];
        foreach ([3 => 'admin', 5 => 'teacher', 6 => 'student'] as $ug => $label) {
            $usersByGroup[$label] = User::where('school_id', $school->id)
                ->where('usergroup_id', $ug)
                ->where('status', 'active')
                ->count();
        }

        $studentAcademics = StudentAcademic::where('school_id', $school->id)
            ->with(['user.userprofile'])
            ->get()
            ->map(function (StudentAcademic $sa) {
                $sectionName = null;
                if ($sa->standardLink_id) {
                    $link = StandardLink::with('section')->find($sa->standardLink_id);
                    $sectionName = $link?->section?->name;
                }

                $firstname = optional($sa->user?->userprofile)->firstname;

                return [
                    'firstname' => is_string($firstname) ? strtoupper($firstname) : $firstname,
                    'section' => $sectionName,
                    'school_student_id' => $sa->school_student_id,
                    'board_registration_number' => $sa->board_registration_number,
                ];
            })
            ->sortBy(fn ($row) => ($row['firstname'] ?? '').'|'.($row['section'] ?? ''))
            ->values()
            ->all();

        $whatsapp = WhatsAppUser::where('school_id', $school->id)
            ->orderBy('phone')
            ->get()
            ->map(fn (WhatsAppUser $w) => [
                'opted_in' => (bool) $w->opted_in,
                'verified' => $w->verified_at !== null,
                // Phone differs per path by design (unique constraint); assert shape only
            ])
            ->values()
            ->all();

        $plan = CurrentPlan::where('school_id', $school->id)->first();
        $planName = $plan ? optional(Plan::find($plan->plan_id))->name : null;

        $links = StandardLink::where('school_id', $school->id)->count();

        return [
            'school' => [
                'curriculum' => $school->curriculum,
                'registration_country' => $school->registration_country,
                // ministry_code is globally unique — compare filled-ness only
                'ministry_code_set' => filled($school->ministry_code),
                'uneb_center_number' => $school->uneb_center_number,
                'school_category' => $school->school_category,
                'toshi_enabled' => (int) $school->toshi_enabled,
            ],
            'standards' => $standards,
            'sections' => $sections,
            'standard_link_count' => $links,
            'subjects' => $subjects,
            'terms' => $terms,
            'fees' => $fees,
            'users_by_usergroup' => $usersByGroup,
            'student_academics' => $studentAcademics,
            'whatsapp_users' => $whatsapp,
            // plan name only — complete-mode persistSelectedPlan vs savePlan status diverge (1D-b)
            'current_plan' => $planName,
            'blocking_incomplete' => OnboardingStepsService::hasBlockingIncompleteSteps(
                $school,
                User::where('school_id', $school->id)->where('usergroup_id', 3)->value('id')
            ),
            'category_known' => array_key_exists((string) $school->school_category, SchoolCategorySeeder::CATEGORIES),
        ];
    }
}
