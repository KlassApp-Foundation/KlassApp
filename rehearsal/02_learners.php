<?php
require __DIR__.'/boot.php';

use App\Imports\UsersImport;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

$s = state();
$admin = User::findOrFail($s['admin_id']);
Auth::login($admin);

$fixture = json_decode(file_get_contents(getenv('HOME').'/Downloads/report-card-handoff/report-card-handoff/fixtures/p5-synthetic-class.json'), true);
$path = __DIR__.'/p5-learners.csv';
$fh = fopen($path, 'w');
fputcsv($fh, ['firstname', 'lastname', 'gender', 'date_of_birth', 'class', 'country', 'joining_date']);
foreach ($fixture['learners'] as $i => $learner) {
    [$first, $last] = array_pad(explode(' ', $learner['name'], 2), 2, '');
    fputcsv($fh, [$first, $last, $i % 2 ? 'female' : 'male', '2014-05-0'.(($i % 9) + 1), 'Primary Five', 'Uganda', '2026-02-02']);
}
fclose($fh);

$plan = app(PlanLimitService::class)->status((int) $s['school_id'], 'students');
say('plan remaining before: '.json_encode($plan));

$import = new UsersImport($plan['remaining']);
Excel::import($import, $path);

$learners = User::query()->where('school_id', $s['school_id'])->where('usergroup_id', 6)->orderBy('id')->get();
say('learners imported: '.$learners->count());
if ($learners->isEmpty()) { exit(1); }

$map = [];
foreach ($learners as $i => $learner) {
    $fixtureKls = $fixture['learners'][$i]['kls'];
    $map[$fixtureKls] = $learner->registration_number;
}
$s['kls_map'] = $map;
save_state($s);
say('first map: '.array_key_first($map).' -> '.reset($map));
