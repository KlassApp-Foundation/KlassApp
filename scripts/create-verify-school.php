<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SchoolSignupBootstrapService;
use App\Services\OnboardingEngine;
use App\Models\School;
use App\Models\User;
use App\Models\StandardLink;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;

$ts = time();
$email = "vf-ts{$ts}@v.test";
$phone = "+25678" . str_pad($ts % 10000000, 7, '0', STR_PAD_LEFT);

echo "Creating school with email: {$email}\n";

// Bootstrap
$bootstrap = app(SchoolSignupBootstrapService::class);
$admin = $bootstrap->bootstrap([
    'name' => 'Verify Admin',
    'email' => $email,
    'phone' => $phone,
    'password' => bcrypt('Test123!'),
]);
$school = $admin->school;
echo "School ID: {$school->id}\n";

// Set name
$engine = app(OnboardingEngine::class);
$engine->saveSchoolName($school, 'Seeder Fix Verify School');
$engine->saveCountry($school, 'Uganda');
$engine->saveCurriculum($school, 'uneb');
$engine->saveSchoolCategory($school, 'primary'); // THIS triggers SchoolCategorySeeder::seed()
$engine->saveEmis($school, "VF{$ts}");

echo "\n=== Now checking subjects ===\n";
$subjects = Subject::where('school_id', $school->id)->get();
echo "Total subjects: {$subjects->count()}\n";

// Verify section_id values
$nullSecSubjects = $subjects->filter(fn($s) => $s->section_id === null);
$nonNullSubjects = $subjects->filter(fn($s) => $s->section_id !== null);
echo "section_id=null: {$nullSecSubjects->count()}\n";
echo "section_id!=null: {$nonNullSubjects->count()}\n";

echo "\nAll subjects:\n";
foreach ($subjects as $s) {
    $secName = $s->section?->name ?? '(nil)';
    echo "  [{$s->id}] {$s->name} section={$secName} section_id={$s->section_id}\n";
}

// Verify forSection() works
$p1 = Section::where('school_id', $school->id)->where('name', 'Primary One')->first();
$p3 = Section::where('school_id', $school->id)->where('name', 'Primary Three')->first();
$p7 = Section::where('school_id', $school->id)->where('name', 'Primary Seven')->first();

echo "\n=== forSection() verification ===\n";
if ($p1) {
    $c1 = Subject::where('school_id', $school->id)->forSection($p1->id)->count();
    echo "P1 (id={$p1->id}): {$c1} subjects\n";
}
if ($p3) {
    $c3 = Subject::where('school_id', $school->id)->forSection($p3->id)->count();
    echo "P3 (id={$p3->id}): {$c3} subjects\n";
}
if ($p7) {
    $c7 = Subject::where('school_id', $school->id)->forSection($p7->id)->count();
    echo "P7 (id={$p7->id}): {$c7} subjects\n";
}

// Also check StandardLinks
$links = StandardLink::where('school_id', $school->id)->with('section')->get();
echo "\nClasses created:\n";
foreach ($links as $l) {
    echo "  [{$l->id}] {$l->section->name}\n";
}

echo "\nSchool ID: {$school->id}\n";
echo "Admin email: {$email}\n";
echo "Admin password: Test123!\n";
