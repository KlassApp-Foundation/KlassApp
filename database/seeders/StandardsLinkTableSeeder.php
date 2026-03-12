<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\Standard;
use App\Models\Section;
use App\Models\User;
use App\Models\StandardLink;
use App\Models\RoleUser;
use App\Helpers\SiteHelper;

class StandardsLinkTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools found. Skipping standards linking.');
            return;
        }

        $classTeacherRoleId = 5; // Assuming role_id=5 is 'class_coordinator' from your earlier roles seeder

        foreach ($schools as $school) {
            $standards = Standard::where('school_id', $school->id)->get();
            $sections  = Section::where('school_id', $school->id)->get();
            $teachers  = User::where([
                'school_id'     => $school->id,
                'usergroup_id'  => 5, // teachers
            ])->pluck('id')->toArray();

            if ($standards->isEmpty() || $sections->isEmpty() || empty($teachers)) {
                $this->command->info("Skipping school ID {$school->id} — missing standards, sections, or teachers.");
                continue;
            }

            $academicYear = SiteHelper::getAcademicyear($school->id);

            if (! $academicYear) {
                $this->command->warn("No active academic year for school ID {$school->id}. Skipping.");
                continue;
            }

            $teacherIndex = 0; // Cycle through teachers if not enough

            foreach ($standards as $standard) {
                foreach ($sections as $section) {
                    // Cycle through available teachers
                    $classTeacherId = $teachers[$teacherIndex % count($teachers)];
                    $teacherIndex++;

                    // Create StandardLink
                    StandardLink::factory()->create([
                        'school_id'         => $school->id,
                        'academic_year_id'  => $academicYear->id,
                        'standard_id'       => $standard->id,
                        'section_id'        => $section->id,
                        'class_teacher_id'  => $classTeacherId,
                    ]);

                    // Assign class coordinator role to this teacher for this class
                    RoleUser::firstOrCreate(
                        [
                            'user_id' => $classTeacherId,
                            'role_id' => $classTeacherRoleId,
                        ],
                        [] // no extra fields needed
                    );
                }
            }

            $this->command->info("Linked standards & sections for school: {$school->name} ({$standards->count()} × {$sections->count()} combos)");
        }
    }
}