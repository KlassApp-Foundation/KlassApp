<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, School, Subject, User};
use App\Services\Reports\ReportScoring;
use App\Services\Results\{ResultsQuery, ResultsService};

$s = state();
$school = School::findOrFail($s['school_id']);
$term = AcademicTerm::findOrFail($s['term_id']);
$fixture = json_decode(file_get_contents(getenv('HOME').'/Downloads/report-card-handoff/report-card-handoff/fixtures/p5-synthetic-class.json'), true);
$learners = User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->orderBy('id')->get()->values();
$subjects = Subject::query()->where('school_id', $school->id)->orderBy('id')->get()->values();

$scoring = app(ReportScoring::class);
$sheet = app(ResultsService::class)->sheet(
    new ResultsQuery(schoolId: (int) $school->id, sectionId: (int) $s['section_id'], academicTermId: (int) $term->id),
    $scoring->policy((int) $school->id, (int) $s['standard_id'], $scoring->countsMissingAsZero((int) $school->id, (int) $s['section_id'], (int) $term->id)),
);

$mismatch = [];
$checked = 0;
$sittings = [];
foreach ($sheet->sittings as $sitting) { $sittings[strtolower($sitting->name)] = $sitting; }
say('sittings: '.implode(', ', array_map(fn ($x) => $x->name, $sheet->sittings)));

foreach ($fixture['learners'] as $i => $want) {
    $learner = $learners[$i];
    foreach ($fixture['sets'] as $setIndex => $setName) {
        $sitting = $sittings[strtolower($setName)] ?? null;
        if (! $sitting) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'sitting', 'want' => 'present', 'got' => 'missing']; continue; }
        $row = $sitting->student((int) $learner->id);
        $exp = $fixture['expected'][$i]['sets'][$setIndex];
        if (! $row) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'row', 'want' => 'present', 'got' => 'missing']; continue; }

        foreach ($subjects as $k => $subject) {
            $cell = $row->cell((int) $subject->id);
            $checked++;
            $wantMark = $want['marks'][$setIndex][$k];
            $wantStatus = $wantMark === 'ABS' ? 'absent' : ($wantMark === null ? 'missing' : 'marked');
            $gotStatus = $cell?->status;
            if ($gotStatus !== $wantStatus) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'status:'.$subject->name, 'want' => $wantStatus, 'got' => $gotStatus]; }
            if ($cell && $wantMark !== null && $wantMark !== 'ABS' && abs((float) $cell->mark - (float) $wantMark) > 0.001) {
                $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'mark:'.$subject->name, 'want' => $wantMark, 'got' => $cell->mark];
            }
            if ($cell && $cell->points !== $exp['points'][$k]) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'points:'.$subject->name, 'want' => $exp['points'][$k], 'got' => $cell->points]; }
            if ($cell && $cell->grade !== $exp['grades'][$k]) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => 'grade:'.$subject->name, 'want' => $exp['grades'][$k], 'got' => $cell->grade]; }
        }
        foreach (['total', 'aggregate', 'division', 'position'] as $field) {
            $got = match ($field) { 'total' => $row->total, 'aggregate' => $row->aggregate, 'division' => $row->division, 'position' => $row->classPosition };
            $wantV = $exp[$field];
            $same = in_array($field, ['total'], true) ? abs((float) $got - (float) $wantV) < 0.001 : (string) $got === (string) $wantV;
            if (! $same) { $mismatch[] = ['learner' => $i + 1, 'set' => $setName, 'field' => $field, 'want' => $wantV, 'got' => $got]; }
        }
    }
}

$byField = [];
foreach ($mismatch as $m) { $key = $m['field']; $byField[$key] = ($byField[$key] ?? 0) + 1; }
$s['parity'] = ['checked_cells' => $checked, 'mismatches' => count($mismatch), 'by_field' => $byField, 'sample' => array_slice($mismatch, 0, 12)];
save_state($s);
say('cells checked: '.$checked.' mismatches: '.count($mismatch));
say(json_encode($byField, JSON_PRETTY_PRINT));
say(json_encode(array_slice($mismatch, 0, 8), JSON_PRETTY_PRINT));
