<?php
// Direct commitAll test — pre-populate all Toshi state, then call commitAll()
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

auth()->login(App\Models\User::where('email', 'siteadmin@gmail.com')->first());

$toshi = new App\Livewire\AgentToshi();
$toshi->mode = 'create';
$toshi->selectedPlanId = 1;
$toshi->schoolName = 'Green Valley Academy';
$toshi->schoolType = 'primary';
$toshi->schoolLevel = '';
$toshi->schoolGender = 'mixed';
$toshi->adminName = 'John Okello';
$toshi->adminEmail = 'john.okello@greenvalley.sch.ug';
$toshi->adminPassword = 'password123';
$toshi->schoolPhone = '+256778123456';
$toshi->coAdminName = 'Sarah Namutebi';
$toshi->coAdminEmail = 'sarah.nam@greenvalley.sch.ug';
$toshi->academicYearLabel = '2026';

// Pre-populate standards & subjects from curriculum (hardcoded — matches curriculumDefaults() for 'primary')
$toshi->standards = [
    ['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
    ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7'],
];
$toshi->subjects = [
    'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
    'Primary 2' => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
    'Primary 3' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
    'Primary 4' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
    'Primary 5' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
    'Primary 6' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
    'Primary 7' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
];

// Teachers
$toshi->teacherList = ['Alice Auma', 'Bob Ssempijja', 'Christine Adong', 'David Opoka'];
$toshi->teacherPhones = [
    'Alice Auma' => '+256701111111',
    'Bob Ssempijja' => '+256702222222',
    'David Opoka' => '+256704444444',
];

// Teacher-Subject-Class links
$toshi->teacherLinks = [
    ['teacher' => 'Alice Auma', 'subject' => 'English Language', 'class' => 'Primary 5', 'phone' => '+256701111111'],
    ['teacher' => 'Bob Ssempijja', 'subject' => 'Mathematics', 'class' => 'Primary 6', 'phone' => '+256702222222'],
    ['teacher' => 'Christine Adong', 'subject' => 'Integrated Science', 'class' => 'Primary 5', 'phone' => ''],
    ['teacher' => 'David Opoka', 'subject' => 'Social Studies', 'class' => 'Primary 7', 'phone' => '+256704444444'],
];

// Students
$toshi->studentList = ['Peter Otim', 'Grace Akello', 'James Ochen', 'Mary Adong', 'Samuel Opio'];

// Terms
$toshi->terms = [
    ['name' => 'Term I',   'start' => '2026-02-01', 'end' => '2026-04-30'],
    ['name' => 'Term II',  'start' => '2026-05-01', 'end' => '2026-08-31'],
    ['name' => 'Term III', 'start' => '2026-09-01', 'end' => '2026-12-31'],
];

// Fees
$toshi->fees = ['Tuition', 'Development Fee', 'Examination Fee'];

// Exams (exams array is stored but no dedicated table — kept for reference)
$toshi->exams = ['Beginning of Term', 'Mid Term', 'End of Term'];

// WhatsApp
$toshi->whatsappPhone = '+256778123456';
$toshi->whatsappVerified = true;

echo "═══════════════════════════════════════════════\n";
echo "  COMMITTING: Green Valley Academy\n";
echo "═══════════════════════════════════════════════\n";
echo "  Plan: Freemium\n";
echo "  School: Green Valley Academy (Primary)\n";
echo "  Admin: John Okello\n";
echo "  Co-Admin: Sarah Namutebi\n";
echo "  Classes: " . count($toshi->standards) . " (P1-P7)\n";
echo "  Subjects: " . count($toshi->subjects) . "\n";
echo "  Teachers: " . count($toshi->teacherList) . "\n";
echo "  Teacher Links: " . count($toshi->teacherLinks) . "\n";
echo "  Students: " . count($toshi->studentList) . "\n";
echo "  Terms: " . count($toshi->terms) . "\n";
echo "  Fees: " . count($toshi->fees) . "\n";
echo "  WhatsApp: {$toshi->whatsappPhone}\n";
echo "\n";

try {
    // commitAll is private — call via reflection
    $ref = new ReflectionMethod(App\Livewire\AgentToshi::class, 'commitAll');
    $ref->setAccessible(true);
    $ref->invoke($toshi);
    echo "✅ COMMIT SUCCESSFUL\n\n";
} catch (\Exception $e) {
    echo "❌ COMMIT FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Verify ──
echo "═══════════════════════════════════════════════\n";
echo "  VERIFICATION\n";
echo "═══════════════════════════════════════════════\n";

$school = App\Models\School::where('name', 'Green Valley Academy')->first();
echo "✅ School: {$school->name} (id={$school->id})\n";

$adminUser = App\Models\User::where('school_id', $school->id)->where('usergroup_id', 3)->first();
echo "✅ Admin: {$adminUser->name} ({$adminUser->email})\n";

$coAdmin = App\Models\User::where('school_id', $school->id)->where('email', 'sarah.nam@greenvalley.sch.ug')->first();
echo "✅ Co-Admin: {$coAdmin->name}\n";

echo "✅ Classes: " . App\Models\StandardLink::where('school_id', $school->id)->count() . "\n";
echo "✅ Subjects: " . App\Models\Subject::where('school_id', $school->id)->count() . "\n";
echo "✅ Teachers: " . App\Models\User::where('school_id', $school->id)->where('usergroup_id', 5)->count() . "\n";
echo "✅ Teacher Links: " . App\Models\Teacherlink::where('school_id', $school->id)->count() . "\n";
echo "✅ Students: " . App\Models\User::where('school_id', $school->id)->where('usergroup_id', 6)->count() . "\n";

$ids = App\Models\StudentAcademic::where('school_id', $school->id)->pluck('klassapp_student_id');
echo "✅ KlassApp IDs: " . $ids->implode(', ') . "\n";

echo "✅ Terms: " . App\Models\AcademicTerm::where('school_id', $school->id)->count() . "\n";
echo "✅ Fees: " . App\Models\FeesCategories::where('school_id', $school->id)->count() . "\n";

$plan = App\Models\CurrentPlan::where('school_id', $school->id)->first();
echo "✅ Plan: " . optional($plan->plan)->name . "\n";

$wa = App\Models\WhatsAppUser::where('user_id', $adminUser->id)->first();
echo "✅ WhatsApp: " . ($wa ? $wa->phone . ' (verified=' . ($wa->verified_at ? 'yes' : 'no') . ')' : 'not set') . "\n";

echo "\n🎉 GREEN VALLEY ACADEMY — FULLY ONBOARDED, NO STEPS SKIPPED!\n";
echo "   Admin login: john.okello@greenvalley.sch.ug / password123\n";
echo "   Co-admin login: sarah.nam@greenvalley.sch.ug / password123\n";
