<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Real klassapp-*-test-data.xlsx fixtures: student stream auto-create +
 * teacher Literacy/Baby Class subject resolution.
 */
class RealSpreadsheetUploadPlacementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private OnboardingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Spreadsheet Fixture School',
            'email' => 'fixture.'.Str::random(6).'@t.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary_nursery',
            'toshi_enabled' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        SchoolCategorySeeder::seed($this->school->fresh());

        // Extend to full K-12 so Senior rows in the student file can resolve.
        foreach ([
            'o-level' => ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'],
            'a-level' => ['Senior Five', 'Senior Six'],
        ] as $level => $sections) {
            $std = Standard::firstOrCreate(
                ['school_id' => $this->school->id, 'name' => $level],
                ['order' => $level === 'o-level' ? 3 : 4, 'status' => '1']
            );
            foreach ($sections as $name) {
                $sec = Section::firstOrCreate(
                    ['school_id' => $this->school->id, 'name' => $name],
                    ['status' => '1']
                );
                StandardLink::firstOrCreate([
                    'school_id' => $this->school->id,
                    'academic_year_id' => $this->year->id,
                    'standard_id' => $std->id,
                    'section_id' => $sec->id,
                    'status' => '1',
                ]);
            }
        }

        DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Fixture Admin',
            'email' => 'admin.'.Str::random(6).'@t.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Fixture',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->engine = app(OnboardingEngine::class);
    }

    public function test_student_fixture_places_all_rows_when_base_classes_exist_without_precreated_streams(): void
    {
        $drafts = $this->studentDraftsFromFixture();
        $this->assertCount(34, $drafts);

        // Precondition: streamed sections do not exist yet (setup-order footgun).
        $this->assertFalse(
            Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->exists()
        );

        $result = $this->engine->saveStudents($this->school, $this->year, $drafts);

        $this->assertCount(34, $result['created'] ?? []);
        $this->assertSame(34, StudentAcademic::where('school_id', $this->school->id)->count());
        $this->assertTrue(
            Section::where('school_id', $this->school->id)->where('name', 'Primary One A')->exists()
        );
        $this->assertTrue(
            Section::where('school_id', $this->school->id)->where('name', 'Baby Class E')->exists()
        );
    }

    public function test_teacher_fixture_literacy_resolves_for_baby_class(): void
    {
        $baby = $this->engine->resolveStandardLinkForClass($this->school, $this->year, 'Baby Class');
        $this->assertNotNull($baby);

        $subject = $this->engine->resolveOrCreateSubjectForClass(
            $this->school,
            $this->year,
            $baby,
            'Literacy'
        );

        $this->assertNotNull($subject);
        // Subject::$name accessor uppercases for display.
        $this->assertSame('LITERACY', $subject->name);
        $this->assertSame((int) $baby->section_id, (int) $subject->section_id);
    }

    public function test_wizard_persists_full_teacher_fixture_including_literacy_on_baby_class(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(
            Subject::where('school_id', $this->school->id)->where('name', 'Literacy')->exists()
        );

        $drafts = $this->teacherDraftsFromFixture();
        $this->assertGreaterThanOrEqual(8, count($drafts));

        $component = Livewire::test(ManualOnboardingWizard::class);
        $teachersIdx = $this->stepIndexFor($component, 'teachers');
        $component
            ->call('goToStep', $teachersIdx)
            ->call('goToStep', $teachersIdx)
            ->set('teacherDrafts', $drafts)
            ->call('next');

        $this->assertSame('', $component->get('errorMessage'));
        $this->assertGreaterThan(0, Teacherlink::where('school_id', $this->school->id)->count());
        $this->assertTrue(
            User::where('school_id', $this->school->id)->where('email', 'dokello@school.ug')->exists()
        );

        $david = User::where('school_id', $this->school->id)->where('email', 'dokello@school.ug')->first();
        $literacy = Subject::where('school_id', $this->school->id)
            ->where('name', 'Literacy')
            ->whereHas('section', fn ($q) => $q->where('name', 'Baby Class'))
            ->first();
        $this->assertNotNull($literacy);
        $this->assertTrue(
            Teacherlink::where('school_id', $this->school->id)
                ->where('teacher_id', $david->id)
                ->where('subject_id', $literacy->id)
                ->exists()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentDraftsFromFixture(): array
    {
        $path = base_path('tests/fixtures/klassapp-students-test-data.xlsx');
        $this->assertFileExists($path);

        $rows = IOFactory::load($path)->getSheetByName('Students')->toArray(null, true, true, false);
        array_shift($rows);

        $drafts = [];
        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }
            $drafts[] = [
                'name' => (string) $row[0],
                'class' => (string) $row[1],
                'stream' => (string) ($row[2] ?? ''),
                'gender' => (string) ($row[3] ?? ''),
                'parent' => (string) ($row[4] ?? ''),
                'parent_phone' => (string) ($row[5] ?? ''),
                'school_student_id' => (string) ($row[6] ?? ''),
                'lin' => (string) ($row[7] ?? ''),
                'board_registration_number' => (string) ($row[8] ?? ''),
                'date_of_birth' => (string) ($row[9] ?? ''),
            ];
        }

        return $drafts;
    }

    /**
     * @return list<array{name: string, email: string, phone: string, subjects: list<string>, classes: list<string>}>
     */
    private function teacherDraftsFromFixture(): array
    {
        $path = base_path('tests/fixtures/klassapp-teachers-test-data.xlsx');
        $this->assertFileExists($path);

        $rows = IOFactory::load($path)->getSheetByName('Teacher Upload')->toArray(null, true, true, false);
        array_shift($rows);

        $drafts = [];
        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }
            $drafts[] = [
                'name' => (string) $row[0],
                'email' => (string) ($row[1] ?? ''),
                'subjects' => $this->splitList((string) ($row[2] ?? '')),
                'classes' => $this->splitList((string) ($row[3] ?? '')),
                'phone' => (string) ($row[4] ?? ''),
            ];
        }

        return $drafts;
    }

    /**
     * @return list<string>
     */
    private function splitList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;|]+/', $raw) ?: [])));
    }

    private function stepIndexFor(object $component, string $key): int
    {
        foreach ($component->instance()->steps as $i => $step) {
            if (($step['key'] ?? '') === $key) {
                return $i;
            }
        }

        $this->fail("Step {$key} not found");
    }
}
