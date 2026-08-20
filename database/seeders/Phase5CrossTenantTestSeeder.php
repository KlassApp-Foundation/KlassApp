<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Phase5CrossTenantTestSeeder extends Seeder
{
    public function run(): void
    {
        // --- School B (school_id + email unique-keyed) ---
        $schoolB = School::firstOrCreate(
            ['name' => 'Phase 5 Cross-Tenant School'],
            [
                'email' => 'phase5-cross-tenant@klassapp.xyz',
                'slug' => 'phase5-cross-tenant',
                'phone' => '070' . random_int(1000000, 9999999),
                'registration_country' => 'Uganda',
                'curriculum' => 'UNEB',
                'status' => 1,
                'toshi_enabled' => 0,
            ],
        );

        // --- Schooladmin for School B ---
        $adminB = $this->user(
            'phase5.admin@klassapp.xyz',
            'Phase 5 Admin',
            $schoolB,
            3,
        );

        // --- Teacher for School B ---
        $teacherB = $this->user(
            'phase5.teacher@klassapp.xyz',
            'Phase 5 Teacher',
            $schoolB,
            5,
        );

        // --- Academic Year for School B ---
        $yearB = AcademicYear::firstOrCreate(
            ['school_id' => $schoolB->id, 'name' => '2026 Cross-Tenant'],
            [
                'description' => 'Phase 5 cross-tenant test year',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ],
        );

        // --- Academic Term for School B ---
        AcademicTerm::firstOrCreate(
            [
                'school_id' => $schoolB->id,
                'academic_year_id' => $yearB->id,
                'name' => 'Term 1',
            ],
            [
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-04-30',
                'status' => 'current',
            ],
        );

        // --- Standard for School B (order NOT NULL) ---
        $standardB = Standard::firstOrCreate(
            ['school_id' => $schoolB->id, 'name' => 'primary'],
            ['order' => 1, 'status' => 1],
        );

        // --- Section for School B ---
        $sectionB = Section::firstOrCreate(
            ['school_id' => $schoolB->id, 'name' => 'P.5 Cross-Tenant'],
            ['status' => 1],
        );
        $sectionB->forceFill(['status' => 1])->save();

        // --- Standards Link (stream) for School B ---
        $streamB = StandardLink::firstOrCreate(
            [
                'school_id' => $schoolB->id,
                'academic_year_id' => $yearB->id,
                'section_id' => $sectionB->id,
                'stream' => 'A',
            ],
            [
                'standard_id' => $standardB->id,
                'class_teacher_id' => $teacherB->id,
                'no_of_students' => 2,
                'status' => 1,
            ],
        );
        $streamB->forceFill([
            'standard_id' => $standardB->id,
            'class_teacher_id' => $teacherB->id,
            'no_of_students' => 2,
            'status' => 1,
        ])->save();

        // --- Subjects for School B (keyed on school_id+section_id+name per verified schema) ---
        Subject::firstOrCreate(
            [
                'school_id' => $schoolB->id,
                'section_id' => $sectionB->id,
                'name' => 'Science',
            ],
            [
                'academic_year_id' => $yearB->id,
                'standard_id' => $standardB->id,
                'type' => 'core',
            ],
        );

        Subject::firstOrCreate(
            [
                'school_id' => $schoolB->id,
                'section_id' => $sectionB->id,
                'name' => 'Social Studies',
            ],
            [
                'academic_year_id' => $yearB->id,
                'standard_id' => $standardB->id,
                'type' => 'core',
            ],
        );

        // --- Students for School B ---
        foreach ([
            ['email' => 'phase5.student.one@klassapp.xyz', 'name' => 'Phase 5 Student One'],
            ['email' => 'phase5.student.two@klassapp.xyz', 'name' => 'Phase 5 Student Two'],
        ] as $studentData) {
            $student = $this->user(
                $studentData['email'],
                $studentData['name'],
                $schoolB,
                6,
            );

            StudentAcademic::updateOrCreate(
                [
                    'school_id' => $schoolB->id,
                    'academic_year_id' => $yearB->id,
                    'user_id' => $student->id,
                ],
                [
                    'standardLink_id' => $streamB->id,
                    'academic_status' => null,
                ],
            );
        }

        $this->command?->info('Phase 5 cross-tenant test data seeded.');
        $this->command?->line('School B: ' . $schoolB->name . ' (ID ' . $schoolB->id . ')');
        $this->command?->line('Admin: phase5.admin@klassapp.xyz / demo123');
        $this->command?->line('Teacher: phase5.teacher@klassapp.xyz / demo123');
    }

    private function user(string $email, string $name, School $school, int $usergroupId): User
    {
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user = new User;
        }

        $user->forceFill([
            'email' => $email,
            'school_id' => $school->id,
            'usergroup_id' => $usergroupId,
            'name' => $name,
            'password' => Hash::make('demo123'),
            'status' => 'active',
            'email_verified' => 1,
        ])->save();

        return $user;
    }
}
