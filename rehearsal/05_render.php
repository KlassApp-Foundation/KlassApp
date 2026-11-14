<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, School, StandardLink, User};
use App\Services\StudentReportCardService;

$s = state();
$school = School::findOrFail($s['school_id']);
$term = AcademicTerm::findOrFail($s['term_id']);
$link = StandardLink::findOrFail($s['link_id']);
$service = app(StudentReportCardService::class);

$learners = User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->orderBy('id')->get();
$dir = '/tmp/rehearsal-cards';
@mkdir($dir, 0777, true);
$times = [];
foreach ($learners as $i => $learner) {
    $t0 = microtime(true);
    $pdf = $service->pdfForStudent((int) $school->id, $link, $learner, null, (int) $term->id);
    file_put_contents($dir.'/'.($i + 1).'-'.$learner->registration_number.'.pdf', $pdf);
    $times[] = microtime(true) - $t0;
}
$t0 = microtime(true);
$merged = $service->mergedPdfForLearners((int) $school->id, $link, $learners, (int) $term->id);
file_put_contents($dir.'/class-merged.pdf', $merged);
$s['render'] = [
    'per_card_seconds' => round(array_sum($times) / max(1, count($times)), 3),
    'per_card_max' => round(max($times), 3),
    'all_seconds' => round(array_sum($times), 2),
    'merged_seconds' => round(microtime(true) - $t0, 2),
    'merged_bytes' => strlen($merged),
];
save_state($s);
say(json_encode($s['render']));
