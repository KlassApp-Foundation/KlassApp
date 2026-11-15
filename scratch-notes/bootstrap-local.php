<?php

/**
 * Disposable local school only. Not staging. Invented names.
 * Run: php scratch-notes/bootstrap-local.php
 */

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$password = Hash::make('LocalOnly-2026');

function person(int $schoolId, int $group, string $name, string $email, string $password, string $phone): User
{
    $user = User::query()->where('email', $email)->first();
    if ($user === null) {
        $user = new User;
        $user->email = $email;
    }
    $user->school_id = $schoolId;
    $user->usergroup_id = $group;
    $user->name = $name;
    $user->password = $password;
    $user->status = 'active';
    $user->is_reset = 0;
    $user->mobile_no = $phone;
    $user->email_verified = 1;
    $user->email_verified_at = now();
    $user->save();

    $profile = Userprofile::query()->firstOrNew(['user_id' => $user->id]);
    $profile->school_id = $schoolId;
    $profile->usergroup_id = $group;
    $profile->firstname = $name;
    $profile->save();

    return $user;
}

function school(string $name, string $slug, string $email, string $phone): School
{
    $row = School::query()->where('name', $name)->first();
    if ($row === null) {
        $row = new School;
        $row->slug = $slug;
    }
    $row->name = $name;
    $row->email = $email;
    $row->phone = $phone;
    $row->status = 1;
    $row->is_demo = 1;
    $row->is_test = 1;
    $row->toshi_enabled = 0;
    $row->toshi_mode = 'preview';
    $row->report_template = 'formal';
    $row->save();

    return $row;
}

$sample = school('Sample Junior School', 'sample-junior', 'office@sample-junior.test', '0700000100');
$second = school('Sample North School', 'sample-north', 'office@sample-north.test', '0700000200');

$admin = person($sample->id, 3, 'Sample Admin', 'admin@sample-junior.test', $password, '0700000001');
$head = person($sample->id, 4, 'Sample Head', 'head@sample-junior.test', $password, '0700000002');
$classTeacher = person($sample->id, 5, 'Sample Class Teacher', 'class-teacher@sample-junior.test', $password, '0700000003');
$otherTeacher = person($sample->id, 5, 'Sample Other Teacher', 'other-teacher@sample-junior.test', $password, '0700000004');
person($second->id, 3, 'North Admin', 'admin@sample-north.test', $password, '0700000011');

$year = AcademicYear::query()->firstOrCreate(
    ['school_id' => $sample->id, 'name' => '2026'],
    ['description' => 'Current Academic Year', 'start_date' => '2026-01-06', 'end_date' => '2026-12-04', 'status' => 1],
);
$year->status = 1;
$year->save();
Cache::forget('academic_year_for_school_'.$sample->id);

$primary = Standard::query()->firstOrCreate(
    ['school_id' => $sample->id, 'name' => 'primary'],
    ['order' => 1, 'status' => 1],
);

$p5 = Section::query()->firstOrCreate(
    ['school_id' => $sample->id, 'name' => 'Primary Five'],
    ['status' => 1, 'class_teacher_id' => $classTeacher->id],
);
$p5->class_teacher_id = $classTeacher->id;
$p5->status = 1;
$p5->save();

$p6 = Section::query()->firstOrCreate(
    ['school_id' => $sample->id, 'name' => 'Primary Six'],
    ['status' => 1, 'class_teacher_id' => $otherTeacher->id],
);
$p6->class_teacher_id = $otherTeacher->id;
$p6->status = 1;
$p6->save();

foreach (['English', 'Mathematics', 'Science', 'Social Studies'] as $i => $name) {
    $subject = Subject::query()->firstOrCreate(
        ['school_id' => $sample->id, 'section_id' => $p5->id, 'name' => $name],
        [
            'academic_year_id' => $year->id,
            'standard_id' => $primary->id,
            'code' => 'S'.($i + 1),
            'type' => 'core',
            'status' => 1,
        ],
    );
}

Subject::query()->firstOrCreate(
    ['school_id' => $sample->id, 'section_id' => $p6->id, 'name' => 'English'],
    [
        'academic_year_id' => $year->id,
        'standard_id' => $primary->id,
        'code' => 'P6E',
        'type' => 'core',
        'status' => 1,
    ],
);

AcademicTerm::query()->updateOrCreate(
    ['school_id' => $sample->id, 'academic_year_id' => $year->id, 'name' => 'First Term'],
    ['starts_on' => '2026-02-02', 'ends_on' => '2026-04-30', 'status' => 'current'],
);
AcademicTerm::query()->updateOrCreate(
    ['school_id' => $sample->id, 'academic_year_id' => $year->id, 'name' => 'Third Term'],
    ['starts_on' => '2026-09-07', 'ends_on' => '2026-11-27', 'status' => 'next'],
);

echo json_encode([
    'sample_school_id' => $sample->id,
    'second_school_id' => $second->id,
    'admin_id' => $admin->id,
    'head_id' => $head->id,
    'class_teacher_id' => $classTeacher->id,
    'other_teacher_id' => $otherTeacher->id,
    'primary_five_id' => $p5->id,
    'primary_id' => $primary->id,
    'year_id' => $year->id,
], JSON_PRETTY_PRINT), PHP_EOL;
