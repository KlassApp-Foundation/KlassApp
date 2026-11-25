<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, AcademicYear, School, Section, Standard, StandardLink, Subject, User};
use App\Models\Academics\{ExamType, SchoolGradingSystem};
use App\Services\SchoolSignupBootstrapService;

if (School::where('name', 'Kabale Rehearsal Junior School')->exists()) {
    say('School already exists; delete /tmp/klassapp-rehearsal.sqlite and re-migrate to rebuild.');
    exit(0);
}

$admin = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
    'name' => 'Rehearsal Admin',
    'email' => 'admin@kabale-rehearsal.test',
    'phone' => null,
    'password' => 'rehearsal-pass-123',
    'school_name' => 'Kabale Rehearsal Junior School',
    'registration_country' => 'Uganda',
]);
$school = $admin->school;
$school->forceFill([
    'report_template' => 'formal',
    'is_demo' => 1,
    'status' => 1,
])->save();
say('school '.$school->id.' '.$school->name);

$year = AcademicYear::create([
    'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
    'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
]);
$term = AcademicTerm::create([
    'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Third Term',
    'starts_on' => '2026-09-14', 'ends_on' => '2026-12-04', 'status' => 'current',
]);
say('term '.$term->id.' current');

$standard = Standard::create([
    'school_id' => $school->id, 'name' => 'primary', 'order' => 5, 'status' => 1,
]);
$bands = [
    ['D1', 1, 95, 100], ['D2', 2, 90, 94], ['C3', 3, 75, 89], ['C4', 4, 65, 74],
    ['C5', 5, 60, 64], ['C6', 6, 50, 59], ['P7', 7, 45, 49], ['P8', 8, 40, 44], ['F9', 9, 0, 39],
];
foreach ($bands as [$grade, $points, $min, $max]) {
    SchoolGradingSystem::create([
        'school_id' => $school->id, 'standard_id' => $standard->id,
        'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max,
    ]);
}

$section = Section::create(['school_id' => $school->id, 'name' => 'Primary Five', 'status' => 1]);
$classTeacher = User::factory()->create([
    'school_id' => $school->id, 'usergroup_id' => 5, 'name' => 'Rehearsal Class Teacher',
    'email' => 'class.teacher@kabale-rehearsal.test', 'status' => 'active',
]);
$link = StandardLink::create([
    'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
    'section_id' => $section->id, 'class_teacher_id' => $classTeacher->id, 'status' => 1,
]);

foreach (['English', 'Mathematics', 'Science', 'Social Studies'] as $i => $name) {
    Subject::create([
        'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
        'section_id' => $section->id, 'name' => $name, 'type' => 'core', 'status' => 1,
    ]);
}

$bot = ExamType::visibleToSchool($school->id)->where('code', 'BOT')->first();
$mid = ExamType::visibleToSchool($school->id)->where('code', 'MID')->first();
if (! $bot || ! $mid) { fwrite(STDERR, "Exam types missing\n"); exit(1); }

save_state([
    'school_id' => $school->id, 'year_id' => $year->id, 'term_id' => $term->id,
    'standard_id' => $standard->id, 'section_id' => $section->id, 'link_id' => $link->id,
    'admin_id' => $admin->id, 'teacher_id' => $classTeacher->id,
    'bot_id' => $bot->id, 'mid_id' => $mid->id,
]);
say('standard '.$standard->id.' section '.$section->id.' link '.$link->id);
say('types BOT='.$bot->id.' MID='.$mid->id);
