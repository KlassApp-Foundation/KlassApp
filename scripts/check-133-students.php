<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sid = 133;

echo "=== Academic Years for school 133 ===\n";
$ays = DB::select("SELECT id, name, status FROM academic_years WHERE school_id = ?", [$sid]);
foreach ($ays as $r) echo "  [{$r->id}] {$r->name} status={$r->status}\n";

echo "\n=== Student Academics ===\n";
$sas = DB::select("SELECT sa.id, sa.user_id, sa.standardLink_id, sa.academic_year_id
    FROM student_academics sa WHERE sa.school_id = ?", [$sid]);
foreach ($sas as $r) echo "  [{$r->id}] uid={$r->user_id} sl={$r->standardLink_id} ay={$r->academic_year_id}\n";

echo "\n=== Student Users (ug 6) ===\n";
$users = DB::select("SELECT u.id, u.name, u.status FROM users u WHERE u.school_id = ? AND u.usergroup_id = 6", [$sid]);
foreach ($users as $r) echo "  [{$r->id}] {$r->name} status={$r->status}\n";

// Recreate the student list query to see what happens
echo "\n=== Student List Query Debug ===\n";
$latestSa = DB::table('student_academics as sa')
    ->select('sa.id', 'sa.user_id', 'sa.standardLink_id')
    ->whereIn('sa.academic_year_id', function ($q) {
        $q->select('id')->from('academic_years')->where('status', 1);
    })
    ->whereNull('sa.deleted_at')
    ->orderByDesc('sa.id');

$query = DB::table('users')
    ->where('users.school_id', $sid)
    ->where('users.usergroup_id', 6)
    ->where('users.status', 'active')
    ->whereNull('users.deleted_at')
    ->leftJoin(DB::raw("({$latestSa->toSql()}) as latest_sa"), 'users.id', '=', 'latest_sa.user_id')
    ->addBinding($latestSa->getBindings(), 'join')
    ->leftJoin('standards_link', 'latest_sa.standardLink_id', '=', 'standards_link.id')
    ->leftJoin('sections', 'standards_link.section_id', '=', 'sections.id')
    ->select('users.id', 'users.name', 'users.status', 'sections.name as class_name', 'latest_sa.standardLink_id')
    ->get();

echo "  Results: " . count($query) . "\n";
foreach ($query as $r) {
    echo "  uid={$r->id} name={$r->name} class={$r->class_name} sl={$r->standardLink_id}\n";
}
