<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, ReportDivisionRange, School, StandardLink, User};
use App\Models\Academics\SchoolGradingSystem;
use App\Services\StudentReportCardService;

$mode = $argv[1] ?? 'capture';      // capture | change | restore
$out = $argv[2] ?? '/tmp/cards-'.uniqid();
$s = state();
$school = School::findOrFail($s['school_id']);

if ($mode === 'change') {
    SchoolGradingSystem::where('school_id', $school->id)->where('grade', 'C3')->update(['min_score' => 80]);
    foreach ([['1', null, 10, 1], ['2', 11, 22, 2], ['3', 23, 26, 3], ['4', 27, 30, 4], ['U', 31, null, 5]] as [$l, $min, $max, $pos]) {
        ReportDivisionRange::create(['school_id' => $school->id, 'standard_id' => $s['standard_id'], 'label' => $l, 'min_aggregate' => $min, 'max_aggregate' => $max, 'position' => $pos]);
    }
    $school->forceFill(['ranking_basis' => 'total'])->save();
} elseif ($mode === 'restore') {
    SchoolGradingSystem::where('school_id', $school->id)->where('grade', 'C3')->update(['min_score' => 75]);
    ReportDivisionRange::where('school_id', $school->id)->delete();
    $school->forceFill(['ranking_basis' => null])->save();
}

$term = AcademicTerm::findOrFail($s['term_id']);
$link = StandardLink::findOrFail($s['link_id']);
$service = app(StudentReportCardService::class);
$learners = User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->orderBy('id')->get();
@mkdir($out, 0777, true);
foreach ($learners as $i => $learner) {
    $pdf = $service->pdfForStudent((int) $school->id, $link, $learner, null, (int) $term->id);
    file_put_contents($out.'/'.($i + 1).'.pdf', $pdf);
}
say('rendered '.$learners->count().' to '.$out);
