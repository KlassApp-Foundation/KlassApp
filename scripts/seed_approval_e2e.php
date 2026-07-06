<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\CurrentPlan;
use App\Models\Subscription;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\AcademicTerm;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Str;

DB::table('usergroups')->insertOrIgnore([
    ['id'=>1,'name'=>'superadmin'],['id'=>3,'name'=>'schooladmin'],
    ['id'=>5,'name'=>'teacher'],['id'=>6,'name'=>'student'],
]);
$plan = Plan::firstOrCreate(['name'=>'Growth'],[
    'cycle'=>30,'display_name'=>'Growth','order'=>1,'is_active'=>1,'amount'=>150000,
    'no_of_students'=>500,'no_of_users'=>50,
]);
$school = School::create([
    'name'=>'Approval E2E', 'email'=>'ae2e@test.sch.ug',
    'phone'=>'+2567012'.mt_rand(1000,9999), 'slug'=>'approval-e2e',
    'status'=>'1', 'registration_country'=>'Uganda',
]);
$sid = $school->id;
echo "School ID: $sid\n";

CurrentPlan::create(['school_id'=>$sid, 'plan_id'=>$plan->id]);

$admin = User::create(['school_id'=>$sid,'usergroup_id'=>3,'name'=>'AE Admin','email'=>'ae.admin@test.sch.ug','password'=>Hash::make('test123'),'status'=>'active','email_verified'=>1]);
Userprofile::create(['user_id'=>$admin->id,'school_id'=>$sid,'usergroup_id'=>3,'firstname'=>'AE','lastname'=>'Admin','status'=>'active']);
Subscription::create(['school_id'=>$sid,'user_id'=>$admin->id,'plan_id'=>$plan->id,'status'=>'approved']);
echo "Admin: ae.admin@test.sch.ug / test123\n";

$ay = AcademicYear::create(['school_id'=>$sid,'name'=>date('Y'),'description'=>'Current','start_date'=>now()->startOfYear(),'end_date'=>now()->endOfYear(),'status'=>1]);
$std = Standard::create(['school_id'=>$sid,'name'=>'default','order'=>1,'status'=>'1']);
$sec = Section::create(['school_id'=>$sid,'name'=>'Primary 1','status'=>'1']);
$sl = StandardLink::create(['school_id'=>$sid,'academic_year_id'=>$ay->id,'standard_id'=>$std->id,'section_id'=>$sec->id]);
$subj = Subject::create(['school_id'=>$sid,'academic_year_id'=>$ay->id,'standard_id'=>$std->id,'section_id'=>$sec->id,'name'=>'English','type'=>'core','status'=>'1']);
foreach(['Term I','Term II','Term III'] as $i=>$tn){
    AcademicTerm::create(['school_id'=>$sid,'academic_year_id'=>$ay->id,'name'=>$tn,'start_date'=>now()->addMonths($i*3),'end_date'=>now()->addMonths($i*3+2),'status'=>'current']);
}

$teacher = User::create(['school_id'=>$sid,'usergroup_id'=>5,'name'=>'AE Teacher','email'=>'ae.teacher@test.sch.ug','password'=>Hash::make('test123'),'status'=>'active','email_verified'=>1]);
Userprofile::create(['user_id'=>$teacher->id,'school_id'=>$sid,'usergroup_id'=>5,'firstname'=>'AE','lastname'=>'Teacher','status'=>'active']);
// Give teacher 'leave_applier' and 'principal' designations so they can submit and approve
$teacher->addDesignation('leave_applier');
$teacher->addDesignation('principal');
$teacher->addDesignation('class_coordinator');
$teacher->save();
echo "Teacher: ae.teacher@test.sch.ug / test123\n";
echo "Designations: " . json_encode($teacher->teacher_designations) . "\n";
echo "Teacher ID: {$teacher->id}\n";
echo "StandardLink ID: {$sl->id}\n";
echo "Subject ID: {$subj->id}\n";
