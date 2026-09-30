<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\StudentAcademic;

$ay=36; $school=53; $sl=307; $now=now();
$u = User::create([
    'name' => 'Verify Student',
    'email' => 'synthetic.student.verify53@demo.klassapp.test',
    'password' => Hash::make('Verify2026!'),
    'school_id' => $school,
    'usergroup_id' => 6,
    'status' => 'active',
    'created_at' => $now,
    'updated_at' => $now
]);
$up = UserProfile::create([
    'user_id' => $u->id,
    'firstname' => 'Verify',
    'lastname' => 'Student',
    'school_id' => $school,
    'usergroup_id' => 6
]);
$sa = StudentAcademic::create([
    'user_id' => $u->id,
    'school_id' => $school,
    'academic_year_id' => $ay,
    'standardLink_id' => $sl,
    'created_at' => $now,
    'updated_at' => $now
]);
echo json_encode(['user_id' => $u->id, 'profile_id' => $up->id, 'academic_id' => $sa->id]) . "\n";
