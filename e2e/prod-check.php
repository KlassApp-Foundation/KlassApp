<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check synthetic admin school
$user = DB::table('users')
    ->where('email', 'prodverify.demo-lakeview-junior@demo.klassapp.test')
    ->select('id', 'school_id', 'status', 'usergroup_id')
    ->first();
echo "Admin: " . json_encode($user) . "\n";

// Check StandardLink 307
$sl = DB::table('standards_link')
    ->where('id', 307)
    ->select('id', 'school_id', 'standard_id', 'section_id', 'academic_year_id', 'status', 'label')
    ->first();
echo "SL307: " . json_encode($sl) . "\n";

// Check current academic year for school 53
$ay = DB::table('academic_years')
    ->where('school_id', $user->school_id ?? 53)
    ->where('status', 1)
    ->orderByDesc('id')
    ->first();
echo "AY for admin school: " . json_encode($ay) . "\n";

// Count students in SL 307
$count = DB::table('student_academics')
    ->where('standardLink_id', 307)
    ->where('school_id', $sl->school_id ?? 0)
    ->count();
echo "Students in SL307: {$count}\n";

// Check standard name
$std = DB::table('standards')
    ->where('id', $sl->standard_id ?? 0)
    ->value('name');
echo "Standard name: {$std}\n";
