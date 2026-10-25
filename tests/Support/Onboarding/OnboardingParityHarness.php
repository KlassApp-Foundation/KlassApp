<?php

namespace Tests\Support\Onboarding;

use App\Livewire\ManualOnboardingWizard;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Steps\StepIssue;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use App\Services\WhatsApp\WhatsAppOnboardingOtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

/**
 * Runs one fixture through the wizard and the current Toshi registry driver.
 *
 * Steps that have not moved onto the shared engine are listed in incompleteSteps()
 * and are not given a fake matching write.
 */
final class OnboardingParityHarness
{
    /**
     * Registry steps whose saved rows are compared and anchored to the fixture.
     *
     * @return list<string>
     */
    public static function provedSteps(): array
    {
        return [
            'school_name',
            'student_size',
            'country',
            'curriculum',
            'school_category',
            'emis',
            'uneb_center',
            'academic_year',
            'standards',
            'subjects',
            'students',
            'terms',
            'fees',
            'plan_selection',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function incompleteSteps(): array
    {
        return [
            'teachers' => 'The wizard resolves class and subject names to link ids before saveTeachers. Toshi passes the draft through, and the engine stores a link only when standardLink_id and subject_id are already set. Applying the fixture teachers would not match, so both faces skip the step.',
            'whatsapp_verify' => 'WhatsAppOnboardingOtpService::deliver is mocked so no code is sent. The wizard may continue without a verified number. The Toshi driver cannot skip this step, so it verifies. Those rows are left out of the snapshot.',
            'class_teachers' => 'Class-teacher assignment is part of the teachers step and has not moved to one shared write. Every class_teacher in the snapshot is null.',
            'checklist_workbook' => 'The checklist-workbook ingester is not in the engine yet. Its decision on the same input is null.',
            'onboarding_progress' => 'OnboardingProgress is not in the engine yet. Both faces return the same unavailable summary.',
            'onboarding_completion' => 'OnboardingCompletion is not in the engine yet. The wizard stamps onboarding_finished_at from confirmReview and the Toshi driver does not, so that stamp is omitted. Both faces return the same unavailable summary.',
            'school_type_preset' => 'SchoolTypePreset is not in the engine yet. Both faces type the fixture values. There is no preset object to accept.',
            'confirm_before_write' => 'A confirm card before the write is not in the driver yet. The driver saves on reply, and this harness does not invent a confirmation.',
        ];
    }

    /**
     * @return array{available: false, reason: string}
     */
    public function progressSummary(School $school): array
    {
        unset($school);

        return [
            'available' => false,
            'reason' => self::incompleteSteps()['onboarding_progress'],
        ];
    }

    /**
     * @return array{available: false, reason: string}
     */
    public function completionSummary(School $school): array
    {
        unset($school);

        return [
            'available' => false,
            'reason' => self::incompleteSteps()['onboarding_completion'],
        ];
    }

    public function workbookDecision(School $school, string $key, mixed $raw): null
    {
        unset($school, $key, $raw);

        return null;
    }

    /**
     * Boolean accept/reject for the wizard engine call and Toshi allIssues().
     * Message text is not compared. The workbook face is null until that ingester exists.
     *
     * @return array{wizard: bool, toshi: bool, workbook: null}
     */
    public function acceptance(School $school, string $key, mixed $raw): array
    {
        return [
            'wizard' => $this->wizardWouldAccept($school, $key, $raw),
            'toshi' => $this->toshiWouldAccept($school, $key, $raw),
            'workbook' => $this->workbookDecision($school, $key, $raw),
        ];
    }

    /**
     * Faces show the first fee issue. allIssues() still lists every bad row.
     *
     * @param  list<StepIssue>  $issues
     * @return list<StepIssue>
     */
    public function shownFeeIssues(array $issues): array
    {
        return array_slice($issues, 0, 1);
    }

    /**
     * @return array{school: School, admin: User}
     */
    public function seedSchool(string $placeholderName, string $schoolEmail, string $adminEmail, string $adminName, string $adminPhone): array
    {
        $school = School::create([
            'name' => $placeholderName,
            'email' => $schoolEmail,
            'phone' => '0700'.random_int(100000, 999999),
            'slug' => 'parity-'.substr(md5($adminEmail), 0, 12),
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => config('toshi.default_mode'),
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => $adminName,
            'email' => $adminEmail,
            'mobile_no' => $adminPhone,
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => $adminName,
            'lastname' => '',
            'status' => 'active',
        ]);

        return ['school' => $school, 'admin' => $admin];
    }

    public function runWizard(TestCase $test, User $admin, OnboardingSchoolFixture $fixture, string $savedName, string $emis): SchoolSnapshot
    {
        $test->actingAs($admin);
        $component = Livewire::test(ManualOnboardingWizard::class);

        Assert::assertSame('school_name', $this->wizardStep($component));
        $component->set('schoolName', $savedName)->call('next');
        $this->assertNoWizardError($component, 'school name');

        Assert::assertSame('student_size', $this->wizardStep($component));
        $component->set('studentSize', $fixture->studentSize)->call('next');
        $this->assertNoWizardError($component, 'student size');

        Assert::assertSame('country', $this->wizardStep($component));
        $component->set('countryName', $fixture->country)->call('next');
        $this->assertNoWizardError($component, 'country');

        Assert::assertSame('curriculum', $this->wizardStep($component));
        $component->set('curriculum', $fixture->curriculum)->call('next');
        $this->assertNoWizardError($component, 'curriculum');

        Assert::assertSame('school_category', $this->wizardStep($component));
        $component->set('schoolCategory', $fixture->category)->call('next');
        $this->assertNoWizardError($component, 'school category');

        Assert::assertSame('emis', $this->wizardStep($component));
        $component->set('ministryCode', $emis)->call('next');
        $this->assertNoWizardError($component, 'emis');

        Assert::assertSame('uneb_center', $this->wizardStep($component));
        $component->set('unebCenterNumber', '')->call('next');
        $this->assertNoWizardError($component, 'uneb centre');

        Assert::assertSame('academic_year', $this->wizardStep($component));
        $component
            ->set('academicYearStart', $fixture->academicYearStart)
            ->set('academicYearEnd', $fixture->academicYearEnd)
            ->set('academicYearDescription', 'Current Academic Year')
            ->call('next');
        $this->assertNoWizardError($component, 'academic year');

        Assert::assertSame('standards', $this->wizardStep($component));
        foreach ($fixture->streams as $className => $labels) {
            $sectionId = $this->sectionId($component, $className);
            foreach ($labels as $label) {
                $component->set('structureStreamDrafts', [(string) $sectionId => $label]);
                $component->call('addStructureStream', $sectionId);
                $this->assertNoWizardError($component, "stream {$className} {$label}");
            }
        }
        $component->call('next');
        $this->assertNoWizardError($component, 'standards');

        Assert::assertSame('subjects', $this->wizardStep($component));
        $component
            ->set('subjectSectionId', $this->sectionId($component, $fixture->extraSubjectClass))
            ->set('subjectName', $fixture->extraSubject)
            ->call('addSubjects');
        $this->assertNoWizardError($component, 'subjects');
        $component->set('subjectName', '')->call('next');
        $this->assertNoWizardError($component, 'subjects next');

        Assert::assertSame('teachers', $this->wizardStep($component));
        $component->call('next');
        $this->assertNoWizardError($component, 'teachers skip');

        // Continue from teachers skips other optional steps and lands on the next
        // required one. Students are optional, so open that step and add them.
        Assert::assertSame('terms', $this->wizardStep($component));
        $component->call('goToStep', $this->stepIndex($component, 'students'));
        Assert::assertSame('students', $this->wizardStep($component));
        foreach ($fixture->students as $student) {
            $component
                ->set('studentName', $student['name'])
                ->set('studentClass', $student['class'])
                ->set('studentStream', $student['stream'])
                ->set('studentGender', $student['gender'])
                ->set('studentParent', $student['parent'])
                ->set('studentParentPhone', $student['parent_phone'])
                ->set('studentSchoolStudentId', $student['school_student_id'])
                ->set('studentLin', '')
                ->set('studentBoardRegNumber', $student['board_registration_number'])
                ->set('studentDateOfBirth', $student['date_of_birth'])
                ->call('addStudentDraft');
            $this->assertNoWizardError($component, 'student '.$student['name']);
        }
        $component->call('next');
        $this->assertNoWizardError($component, 'students');

        Assert::assertSame('terms', $this->wizardStep($component));
        $component
            ->set('termDrafts', array_map(fn (array $term): array => [
                'name' => $term['name'],
                'start' => $term['start'],
                'end' => $term['end'],
            ], $fixture->terms))
            ->set('currentTermName', 'Term 1')
            ->set('termName', '')
            ->call('next');
        $this->assertNoWizardError($component, 'terms');

        Assert::assertSame('fees', $this->wizardStep($component));
        $component->set('feeName', '')->set('feeAmount', '');
        foreach ($fixture->fees as $fee) {
            $component
                ->set('feeName', $fee['name'])
                ->set('feeAmount', (string) $fee['amount'])
                ->set('feeScope', $fee['scope'])
                ->set('feeClass', $fee['class'])
                ->set('feeTerm', $fee['yearly'] ? '' : $fee['term'])
                ->set('feeIsYearly', $fee['yearly'])
                ->call('addFeeDraft');
            $this->assertNoWizardError($component, 'fee '.$fee['name']);
        }
        $component->set('feeName', '')->set('feeAmount', '')->call('next');
        $this->assertNoWizardError($component, 'fees');

        Assert::assertSame('whatsapp_verify', $this->wizardStep($component));
        $component->call('next');
        $this->assertNoWizardError($component, 'whatsapp skip');

        Assert::assertSame('plan_selection', $this->wizardStep($component));
        $planId = Plan::query()->where('name', $fixture->planName)->where('is_active', 1)->value('id');
        Assert::assertNotNull($planId, 'Freemium plan missing');
        $component->set('selectedPlanId', (int) $planId)->call('next');
        $this->assertNoWizardError($component, 'plan');

        Assert::assertSame('review', $this->wizardStep($component));
        $component->call('confirmReview');
        $this->assertNoWizardError($component, 'review');
        Assert::assertTrue((bool) $component->get('finished'));

        return SchoolSnapshot::capture($admin->school->fresh());
    }

    public function runToshi(School $school, User $admin, OnboardingSchoolFixture $fixture, string $savedName, string $emis): SchoolSnapshot
    {
        $driver = app(ToshiOnboardingV2Driver::class);
        $userId = $admin->id;

        $standards = [];
        foreach ($fixture->streams as $className => $labels) {
            $standards[] = ['name' => $className, 'streams' => array_values($labels)];
        }

        $replies = [
            'school_name' => $savedName,
            'student_size' => $fixture->studentSize,
            'country' => $fixture->country,
            'curriculum' => $fixture->curriculum,
            'school_category' => $fixture->category,
            'emis' => $emis,
            'uneb_center' => 'skip',
            'academic_year' => [
                'name' => $fixture->academicYearName,
                'start' => $fixture->academicYearStart,
                'end' => $fixture->academicYearEnd,
            ],
            'standards' => $standards,
            'subjects' => [
                $fixture->extraSubjectClass => [$fixture->extraSubject],
            ],
            'teachers' => 'skip',
            'students' => $fixture->students,
            'terms' => $fixture->terms,
            'fees' => array_map(fn (array $fee): array => array_filter([
                'name' => $fee['name'],
                'amount' => $fee['amount'],
                'class' => $fee['scope'] === 'class' ? $fee['class'] : null,
                'term' => $fee['yearly'] ? null : $fee['term'],
                'is_yearly' => $fee['yearly'] ? true : null,
            ], fn ($value) => $value !== null), $fixture->fees),
            'whatsapp_verify' => $fixture->whatsapp,
            'plan_selection' => $fixture->planName,
        ];

        foreach ($replies as $key => $reply) {
            $school = $school->fresh();
            $prompt = $driver->promptNext($school, $userId);
            Assert::assertSame('prompt', $prompt['status'] ?? null, 'Toshi finished before '.$key);
            Assert::assertSame($key, $prompt['key'] ?? null, 'Toshi prompted '.json_encode($prompt).' while the script expected '.$key);

            $result = $driver->handleReply($school, $reply, $userId);
            Assert::assertNotSame(
                'rejected',
                $result['status'] ?? null,
                'Toshi rejected '.$key.': '.($result['hint'] ?? '').' '.json_encode($reply)
            );

            if ($key === 'whatsapp_verify') {
                Assert::assertSame('awaiting', $result['status'] ?? null, 'WhatsApp should ask for the code. '.json_encode($result));
                $code = app(WhatsAppOnboardingOtpService::class)->pendingCode();
                Assert::assertNotNull($code, 'Mocked WhatsApp delivery did not store a code.');
                $result = $driver->handleReply($school->fresh(), $code, $userId);
                Assert::assertNotSame('rejected', $result['status'] ?? null, 'Toshi rejected the WhatsApp code: '.($result['hint'] ?? ''));
            }
        }

        $done = $driver->promptNext($school->fresh(), $userId);
        Assert::assertSame('done', $done['status'] ?? null, 'Toshi did not finish. '.json_encode($done));

        return SchoolSnapshot::capture($school->fresh());
    }

    /**
     * @param  array<string, mixed>  $comparable
     */
    public function assertAnchored(OnboardingSchoolFixture $fixture, array $comparable): void
    {
        Assert::assertNotSame([], $fixture->teachers, 'The fixture must list teachers the harness refuses to fake.');
        Assert::assertSame('2027', $fixture->academicYearName);
        Assert::assertSame('2027-01-01', $comparable['settings']['academic_year']['start'] ?? null);
        Assert::assertSame('2027-12-31', $comparable['settings']['academic_year']['end'] ?? null);
        Assert::assertSame('2027', $comparable['settings']['academic_year']['name'] ?? null);
        Assert::assertSame('', $comparable['settings']['uneb_center_number'] ?? null);
        Assert::assertSame(0, $comparable['settings']['toshi_enabled'] ?? null);
        Assert::assertSame($fixture->planName, $comparable['plan'] ?? null);

        $classNames = array_column($comparable['classes'], 'name');
        Assert::assertEqualsCanonicalizing(
            SchoolCategorySeeder::expectedSectionNames($fixture->category),
            $classNames,
            'Seeded classes plus streams must be the category list, not an empty school.'
        );

        $byName = [];
        foreach ($comparable['classes'] as $class) {
            $byName[$class['name']] = $class;
            Assert::assertNull($class['class_teacher']);
        }
        foreach ($fixture->streams as $className => $labels) {
            $expected = $labels;
            sort($expected, SORT_STRING);
            Assert::assertSame($expected, $byName[$className]['streams'] ?? null, $className.' streams');
        }

        $extra = array_values(array_filter(
            $comparable['subjects'],
            fn (array $subject): bool => $subject['name'] === $fixture->extraSubject && $subject['class'] === $fixture->extraSubjectClass
        ));
        Assert::assertNotSame([], $extra, 'The extra subject was not saved on '.$fixture->extraSubjectClass);

        Assert::assertCount(count($fixture->students), $comparable['students']);
        foreach ($fixture->students as $student) {
            $saved = array_values(array_filter(
                $comparable['students'],
                fn (array $row): bool => $row['name'] === $student['name']
            ));
            Assert::assertCount(1, $saved, $student['name'].' missing');
            Assert::assertSame($student['class'], $saved[0]['class']);
            Assert::assertSame($student['stream'], $saved[0]['stream']);
            Assert::assertSame($student['gender'], $saved[0]['gender']);
            Assert::assertSame($student['date_of_birth'], $saved[0]['date_of_birth']);
            Assert::assertSame($student['school_student_id'], $saved[0]['school_student_id']);
            Assert::assertSame($student['board_registration_number'], $saved[0]['board_registration_number']);
            Assert::assertSame('', $saved[0]['phone']);
            Assert::assertSame('', $saved[0]['lin']);
        }

        Assert::assertCount(count($fixture->students), $comparable['parents']);
        foreach ($fixture->students as $student) {
            $parent = array_values(array_filter(
                $comparable['parents'],
                fn (array $row): bool => $row['name'] === $student['parent']
            ));
            Assert::assertCount(1, $parent, $student['parent'].' missing');
            Assert::assertContains($student['name'], $parent[0]['students']);
            Assert::assertNotSame('', $parent[0]['phone']);
        }

        Assert::assertSame($fixture->terms, $comparable['terms']);

        foreach ($fixture->fees as $fee) {
            $matches = array_values(array_filter(
                $comparable['fees'],
                fn (array $row): bool => $row['name'] === $fee['name']
                    && $row['amount'] === number_format($fee['amount'], 2, '.', '')
                    && ($fee['yearly'] ? $row['term'] === null : $row['term'] === $fee['term'])
                    && ($fee['scope'] === 'class' ? $row['section'] === $fee['class'] : $row['section'] === null)
            ));
            Assert::assertNotSame([], $matches, 'Fee '.$fee['name'].' was not saved in the fixture shape.');
        }
    }

    private function wizardWouldAccept(School $school, string $key, mixed $raw): bool
    {
        DB::beginTransaction();
        try {
            match ($key) {
                'school_name' => app(OnboardingEngine::class)->saveSchoolName($school, (string) $raw),
                'student_size' => app(OnboardingEngine::class)->saveStudentSize($school, (string) $raw),
                default => throw new \InvalidArgumentException('No wizard accept check for '.$key),
            };

            return true;
        } catch (ValidationException) {
            return false;
        } finally {
            DB::rollBack();
        }
    }

    private function toshiWouldAccept(School $school, string $key, mixed $raw): bool
    {
        $step = app(StepRegistry::class)->byKey($key);
        Assert::assertNotNull($step);

        return $step->allIssues($school, $raw) === [];
    }

    private function wizardStep(Testable $component): string
    {
        $steps = $component->get('steps');
        $index = (int) $component->get('stepIndex');

        return (string) ($steps[$index]['key'] ?? '');
    }

    private function stepIndex(Testable $component, string $key): int
    {
        foreach ($component->get('steps') as $index => $step) {
            if (($step['key'] ?? '') === $key) {
                return (int) $index;
            }
        }

        Assert::fail('Wizard has no step '.$key);
    }

    private function assertNoWizardError(Testable $component, string $context): void
    {
        $error = trim((string) $component->get('errorMessage'));
        Assert::assertSame('', $error, $context.' failed on '.$this->wizardStep($component).': '.$error);
    }

    private function sectionId(Testable $component, string $className): int
    {
        foreach ($component->get('structureClasses') as $row) {
            if (strcasecmp((string) ($row['name'] ?? ''), $className) === 0) {
                return (int) $row['section_id'];
            }
        }

        Assert::fail('Wizard structure has no class named '.$className);
    }
}
