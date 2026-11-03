<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\RoleUser;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\User;
use App\Reference\ClassCatalog;
use Illuminate\Database\Seeder;

/**
 * Depends on standards, sections, an active academic year, and at
 * least one teacher. One link per class (section + its standard),
 * stream A. Does not cross every section with every standard.
 */
class StandardsLinkTableSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [];
        foreach (ClassCatalog::classes() as $class) {
            $catalog[$class['name']] = $class['standard'];
        }

        $classTeacherRoleId = 5;

        foreach (School::query()->where('status', 1)->get() as $school) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('status', 1)
                ->first();
            $teachers = User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 5)
                ->pluck('id');
            $standards = Standard::query()->where('school_id', $school->id)->get()->keyBy('name');
            $sections = Section::query()->where('school_id', $school->id)->get();

            if ($year === null || $teachers->isEmpty() || $standards->isEmpty() || $sections->isEmpty()) {
                $this->command?->warn("Skipping standards links for school {$school->id}.");

                continue;
            }

            $index = 0;
            foreach ($sections as $section) {
                $standardName = $catalog[$section->name] ?? null;
                $standard = $standardName === null ? null : $standards->get($standardName);
                if ($standard === null) {
                    continue;
                }

                $teacherId = $teachers[$index % $teachers->count()];
                $index++;

                ReferenceUpsert::sync('standards_link', [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'section_id' => $section->id,
                    'stream' => 'A',
                ], [
                    'standard_id' => $standard->id,
                    'class_teacher_id' => $teacherId,
                    'status' => 1,
                    'deleted_at' => null,
                ]);

                RoleUser::query()->firstOrCreate(
                    [
                        'user_id' => $teacherId,
                        'role_id' => $classTeacherRoleId,
                    ],
                    [
                        'user_type' => User::class,
                    ]
                );
            }
        }
    }
}
