<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\ClassStructureService;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveStudentsStreamedCandidateUnebTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    /** @var array<string, Standard> */
    private array $standards = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Uneb Stream School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 1,
        ]);
    }

    public function test_streamed_candidate_classes_keep_uneb_numbers(): void
    {
        $this->stream('S4', 'East', 'o-level');
        $this->stream('P7', 'Blue', 'primary');
        $this->stream('Senior Four', 'A', 'o-level');
        $this->base('P.7', 'primary');
        $this->base('S6', 'a-level');
        $this->stream('P1', 'Blue', 'primary');
        $this->stream('P1', 'S4', 'primary');
        $this->base('S1', 'o-level');

        $result = app(OnboardingEngine::class)->saveStudents($this->school, $this->year, [
            ['name' => 'S4 East Student', 'class' => 'S4', 'stream' => 'East', 'board_registration_number' => 'U1000/001'],
            ['name' => 'P7 Blue Student', 'class' => 'P7', 'stream' => 'Blue', 'board_registration_number' => 'U1000/002'],
            ['name' => 'Senior Four A Student', 'class' => 'Senior Four', 'stream' => 'A', 'board_registration_number' => 'U1000/003'],
            ['name' => 'Unstreamed P7 Student', 'class' => 'P.7', 'board_registration_number' => 'U1000/004'],
            ['name' => 'Unstreamed S6 Student', 'class' => 'S6', 'board_registration_number' => 'U1000/005'],
            ['name' => 'P1 Blue Student', 'class' => 'P1', 'stream' => 'Blue', 'board_registration_number' => 'U1000/006'],
            ['name' => 'Unstreamed S1 Student', 'class' => 'S1', 'board_registration_number' => 'U1000/007'],
            ['name' => 'P1 Stream S4 Student', 'class' => 'P1', 'stream' => 'S4', 'board_registration_number' => 'U1000/008'],
        ]);

        $this->assertCount(8, $result['created']);

        $this->assertSame('S4 East', $this->sectionName('S4 East Student'));
        $this->assertSame('U1000/001', $this->boardReg('S4 East Student'));

        $this->assertSame('P7 Blue', $this->sectionName('P7 Blue Student'));
        $this->assertSame('U1000/002', $this->boardReg('P7 Blue Student'));

        $this->assertSame('Senior Four A', $this->sectionName('Senior Four A Student'));
        $this->assertSame('U1000/003', $this->boardReg('Senior Four A Student'));

        $this->assertSame('P.7', $this->sectionName('Unstreamed P7 Student'));
        $this->assertSame('U1000/004', $this->boardReg('Unstreamed P7 Student'));

        $this->assertSame('S6', $this->sectionName('Unstreamed S6 Student'));
        $this->assertSame('U1000/005', $this->boardReg('Unstreamed S6 Student'));

        $this->assertSame('P1 Blue', $this->sectionName('P1 Blue Student'));
        $this->assertNull($this->boardReg('P1 Blue Student'));

        $this->assertSame('S1', $this->sectionName('Unstreamed S1 Student'));
        $this->assertNull($this->boardReg('Unstreamed S1 Student'));

        // The stream token is S4, but the base class is P1. The number stays ignored.
        $this->assertSame('P1 S4', $this->sectionName('P1 Stream S4 Student'));
        $this->assertNull($this->boardReg('P1 Stream S4 Student'));
    }

    private function tier(string $name): Standard
    {
        if (! isset($this->standards[$name])) {
            $this->standards[$name] = Standard::create([
                'school_id' => $this->school->id,
                'name' => $name,
                'order' => count($this->standards) + 1,
                'status' => 1,
            ]);
        }

        return $this->standards[$name];
    }

    private function base(string $className, string $tier): Section
    {
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => $className,
            'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->tier($tier)->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        return $section;
    }

    private function stream(string $className, string $stream, string $tier): void
    {
        $section = Section::query()
            ->where('school_id', $this->school->id)
            ->where('name', $className)
            ->first();

        if (! $section) {
            $section = $this->base($className, $tier);
        }

        app(ClassStructureService::class)->addStream($this->school, $this->year, $section, $stream);
    }

    private function boardReg(string $name): ?string
    {
        $user = User::where('school_id', $this->school->id)->where('name', $name)->firstOrFail();

        return StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $user->id)
            ->value('board_registration_number');
    }

    private function sectionName(string $name): ?string
    {
        $user = User::where('school_id', $this->school->id)->where('name', $name)->firstOrFail();
        $linkId = StudentAcademic::where('school_id', $this->school->id)
            ->where('user_id', $user->id)
            ->value('standardLink_id');

        return StandardLink::with('section')->find($linkId)?->section?->name;
    }
}
