<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, School, Subject, User};
use App\Models\Academics\Exam;
use App\Services\Academics\ExamSetService;
use App\Services\MarksImport\MarksImportService;
use Illuminate\Support\Facades\Auth;

$s = state();
$school = School::findOrFail($s['school_id']);
$term = AcademicTerm::findOrFail($s['term_id']);
$admin = User::findOrFail($s['admin_id']);
$sectionId = (int) $s['section_id'];
Auth::login($admin);

$set = app(ExamSetService::class)->create($school, $term, [$s['bot_id'], $s['mid_id']], [$sectionId], $admin);
say('exam set: '.json_encode($set));

$exams = Exam::query()->where('school_id', $school->id)->where('academic_term_id', $term->id)->get();
$byKey = [];
foreach ($exams as $exam) {
    $subject = Subject::find($exam->subject_id);
    $byKey[$exam->exam_type_id.'|'.strtolower(trim($subject->name))] = $exam;
}
say('exams: '.$exams->count());

$fixtureDir = getenv('HOME').'/Downloads/report-card-handoff/report-card-handoff/fixtures/importer-csv';
$map = $s['kls_map'];
$outDir = '/tmp/rehearsal-import';
@mkdir($outDir, 0777, true);
$importer = app(MarksImportService::class);
$report = [];

foreach (glob($fixtureDir.'/*.csv') as $file) {
    [$setName, $subjectName] = explode('__', basename($file, '.csv'));
    $setName = str_replace('-', ' ', $setName);
    $subjectName = str_replace('-', ' ', $subjectName);
    $typeId = str_contains(strtolower($setName), 'beginning') ? $s['bot_id'] : $s['mid_id'];
    $exam = $byKey[$typeId.'|'.strtolower(trim($subjectName))] ?? null;
    if (! $exam) { say("MISSING EXAM $setName / $subjectName"); continue; }

    $rows = array_map('str_getcsv', file($file));
    $out = $outDir.'/'.basename($file);
    $fh = fopen($out, 'w');
    foreach ($rows as $i => $row) {
        if ($i === 0) { fputcsv($fh, $row); continue; }
        if (($row[0] ?? '') === '') { continue; }
        $row[0] = $map[$row[0]] ?? $row[0];
        fputcsv($fh, $row);
    }
    fclose($fh);

    $plan = $importer->preview($exam, $admin, $out, basename($out));
    $result = $importer->commit($plan, $admin, overwrite: true);
    $skipped = [];
    foreach ($result->rows as $row) {
        if (($row['outcome'] ?? '') === 'skipped') { $skipped[$row['reason']] = ($skipped[$row['reason']] ?? 0) + 1; }
    }
    $report[] = ['file' => basename($file), 'saved' => $result->saved, 'updated' => $result->updated, 'skipped' => $skipped];
    say(basename($file).' saved='.$result->saved.' updated='.$result->updated.' skipped='.json_encode($skipped));
}
$s['import_report'] = $report;
save_state($s);
