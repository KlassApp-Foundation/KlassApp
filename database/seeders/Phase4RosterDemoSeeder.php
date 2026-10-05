<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Support\DemoSeedPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * TEST FIXTURE ONLY — this school exists for the Phase 4 roster tests.
 *
 * It must never be used for demos, screenshots or landing material. The single
 * canonical demo school is Demo Academy Uganda (see DemoAcademySeeder); capture
 * scripts and docs must target demo-academy-uganda, never this fixture.
 */
class Phase4RosterDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Key on email: SchoolObserver::created() overwrites slug with Str::slug(name),
        // so a slug-keyed firstOrCreate would never match on re-runs.
        $school = School::firstOrCreate(
            ['email' => 'phase4-roster-demo@klassapp.xyz'],
            [
                'name' => 'Phase 4 Roster Test Fixture',
                'slug' => 'phase4-roster-demo',
                'phone' => '070' . random_int(1000000, 9999999),
                'registration_country' => 'Uganda',
                'curriculum' => 'UNEB',
                'status' => 1,
                'toshi_enabled' => 0,
            ],
        );

        // Clear flags: a test fixture, not a demo, not a customer.
        $school->forceFill(['is_test' => 1, 'is_demo' => 0])->save();

        $year = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026 Demo'],
            [
                'description' => 'Phase 4 roster demonstration year',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ],
        );

        $standard = Standard::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'primary'],
            ['order' => 1, 'status' => 1],
        );

        $this->user('phase4.admin@klassapp.xyz', 'Phase 4 Demo Admin', $school, 3);
        $subjectTeacher = $this->user('phase4.teacher@klassapp.xyz', 'Phase 4 Subject Teacher', $school, 5);
        $classTeacher = $this->user('phase4.class-teacher@klassapp.xyz', 'Phase 4 Class Teacher', $school, 5);

        $section = Section::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'P.4 Demo'],
            ['status' => 1],
        );
        $section->forceFill([
            'class_teacher_id' => $classTeacher->id,
            'status' => 1,
        ])->save();

        $stream = StandardLink::firstOrCreate(
            [
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'section_id' => $section->id,
                'stream' => 'A',
            ],
            [
                'standard_id' => $standard->id,
                'class_teacher_id' => $classTeacher->id,
                'no_of_students' => 3,
                'status' => 1,
            ],
        );
        $stream->forceFill([
            'standard_id' => $standard->id,
            'class_teacher_id' => $classTeacher->id,
            'no_of_students' => 3,
            'status' => 1,
        ])->save();

        $subject = Subject::firstOrCreate(
            [
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'section_id' => $section->id,
                'name' => 'Mathematics',
            ],
            [
                'standard_id' => $standard->id,
                'code' => 'MTC-DEMO',
                'type' => 'core',
                'status' => 1,
            ],
        );

        Teacherlink::firstOrCreate([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
        ]);

        foreach ([
            ['email' => 'phase4.student.one@klassapp.xyz', 'name' => 'Phase 4 Student One'],
            ['email' => 'phase4.student.two@klassapp.xyz', 'name' => 'Phase 4 Student Two'],
            ['email' => 'phase4.student.three@klassapp.xyz', 'name' => 'Phase 4 Student Three'],
        ] as $studentData) {
            $student = $this->user($studentData['email'], $studentData['name'], $school, 6);
            $klsNumber = \App\Services\StudentIdGeneratorService::ensureForStudent($student);

            StudentAcademic::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $student->id,
                ],
                [
                    'standardLink_id' => $stream->id,
                    'klassapp_student_id' => $klsNumber,
                    'academic_status' => 'pass',
                ],
            );
        }

        $this->command?->info('Phase 4 roster demo seeded.');
        $this->command?->line('School: ' . $school->name . ' (ID ' . $school->id . ')');
        $this->command?->line('Admin: phase4.admin@klassapp.xyz');
        $this->command?->line('Teacher: phase4.teacher@klassapp.xyz');
        $this->command?->line('Class teacher: phase4.class-teacher@klassapp.xyz');
        $this->command?->line('Password: not echoed — pin via STAGING_DEMO_PASSWORD / DEMO_SEED_PASSWORD (Doppler/Cloud), or a random value was generated for this run.');
    }

    private function user(string $email, string $name, School $school, int $usergroupId): User
    {
        $user = User::firstOrNew(['email' => $email]);

        // Build directly instead of User::factory()->make(): production installs
        // with --no-dev, so Faker (and the factory) are unavailable.
        if (! $user->exists) {
            $user = new User;
        }

        $user->forceFill([
            'email' => $email,
            'school_id' => $school->id,
            'usergroup_id' => $usergroupId,
            'name' => $name,
            'password' => Hash::make(DemoSeedPassword::resolve()),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        return $user;
    }
}
