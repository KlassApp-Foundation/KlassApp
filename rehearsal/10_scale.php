<?php
/**
 * Scale rehearsal: about 800 learners, 10 classes, 3 exam sets, blanks, ABS, ties.
 * Each phase is timed and written to rehearsal/scale-state.json.
 */
require __DIR__.'/boot.php';

use App\Imports\UsersImport;
use App\Models\{AcademicTerm, AcademicYear, School, Section, Standard, StandardLink, Subject, User};
use App\Models\Academics\{Exam, ExamType, Marks, SchoolGradingSystem};
use App\Services\Academics\ExamSetService;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksImport\MarksImportService;
use App\Services\MarksRelease\MarksReleaseService;
use App\Services\StudentReportCardService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

$phase = $argv[1] ?? 'all';
$stateFile = __DIR__.'/scale-state.json';
$state = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
$mark = function (string $key, float $seconds) use (&$state, $stateFile) {
    $state['timings'][$key] = round($seconds, 2);
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    say(sprintf('%s: %.2fs', $key, $seconds));
};

if (in_array($phase, ['all', 'school'], true)) {
    $t = microtime(true);
    $admin = app(\App\Services\SchoolSignupBootstrapService::class)->bootstrapAsOperator([
        'name' => 'Scale Admin', 'email' => 'admin@scale-rehearsal.test', 'phone' => null,
        'password' => 'rehearsal-pass-123', 'school_name' => 'Scale Rehearsal School', 'registration_country' => 'Uganda',
    ]);
    $school = $admin->school;
    $school->forceFill(['report_template' => 'formal', 'is_demo' => 1, 'status' => 1,
        'exam_type_preferences' => ['1' => '1', '3' => '1', '5' => '1']])->save(); // BOT, MID, EOT ids in the catalog
    $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
    $term = AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Third Term', 'starts_on' => '2026-09-14', 'ends_on' => '2026-12-04', 'status' => 'current']);
    $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 5, 'status' => 1]);
    foreach ([['D1',1,95,100],['D2',2,90,94],['C3',3,75,89],['C4',4,65,74],['C5',5,60,64],['C6',6,50,59],['P7',7,45,49],['P8',8,40,44],['F9',9,0,39]] as [$g,$p,$min,$max]) {
        SchoolGradingSystem::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'grade' => $g, 'points' => $p, 'min_score' => $min, 'max_score' => $max]);
    }
    $teacher = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 5, 'name' => 'Scale Teacher', 'email' => 'teacher@scale-rehearsal.test', 'status' => 'active']);
    $classes = [];
    for ($c = 1; $c <= 10; $c++) {
        $section = Section::create(['school_id' => $school->id, 'name' => 'Primary '.$c, 'status' => 1]);
        $link = StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'status' => 1]);
        $subjectIds = [];
        $subjectNames = array_slice(['English', 'Mathematics', 'Science', 'Social Studies', 'Religious Education', 'Agriculture'], 0, 4 + ($c % 3)); // 4-6
        foreach ($subjectNames as $name) {
            $subjectIds[] = Subject::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'name' => $name, 'type' => 'core', 'status' => 1])->id;
        }
        $classes[] = ['section_id' => $section->id, 'link_id' => $link->id, 'subject_ids' => $subjectIds];
    }
    $state = array_merge($state, ['school_id' => $school->id, 'year_id' => $year->id, 'term_id' => $term->id, 'standard_id' => $standard->id, 'admin_id' => $admin->id, 'teacher_id' => $teacher->id, 'classes' => $classes, 'types' => [1, 3, 5]]);
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    $mark('school_and_classes', microtime(true) - $t);
}

if (in_array($phase, ['all', 'learners'], true)) {
    $t = microtime(true);
    Auth::login(User::find($state['admin_id']));
    $csv = __DIR__.'/scale-learners.csv';
    $fh = fopen($csv, 'w');
    fputcsv($fh, ['firstname', 'lastname', 'gender', 'date_of_birth', 'class', 'country', 'joining_date']);
    for ($c = 1; $c <= 10; $c++) {
        for ($n = 1; $n <= 80; $n++) {
            fputcsv($fh, ['Learner', sprintf('C%02d-%03d', $c, $n), $n % 2 ? 'female' : 'male', '2014-03-0'.(($n % 9) + 1), 'Primary '.$c, 'Uganda', '2026-02-02']);
        }
    }
    fclose($fh);
    $plan = app(\App\Services\PlanLimitService::class)->status((int) $state['school_id'], 'students');
    Excel::import(new UsersImport($plan['remaining']), $csv);
    $count = User::where('school_id', $state['school_id'])->where('usergroup_id', 6)->count();
    $state['learner_count'] = $count;
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    $mark('learners_'.$count, microtime(true) - $t);
}

if (in_array($phase, ['all', 'marks'], true)) {
    Auth::login(User::find($state['admin_id']));
    $school = School::find($state['school_id']);
    $term = AcademicTerm::find($state['term_id']);
    $admin = User::find($state['admin_id']);
    $teacher = User::find($state['teacher_id']);
    $importer = app(MarksImportService::class);
    $writer = app(ExamMarkWriter::class);
    $setService = app(ExamSetService::class);
    mt_srand(20261115);
    $importTimes = [];
    foreach ($state['classes'] as $ci => $class) {
        $section = Section::find($class['section_id']);
        $setService->create($school, $term, $state['types'], [$section->id], $admin);
        $exams = Exam::where('school_id', $school->id)->where('academic_term_id', $term->id)->where('section_id', $section->id)->get();
        $learners = User::where('school_id', $school->id)->where('usergroup_id', 6)->whereHas('studentAcademic', fn ($q) => $q->where('standardLink_id', $class['link_id']))->orderBy('id')->get();
        foreach ($exams as $exam) {
            $subject = Subject::find($exam->subject_id);
            $dir = '/tmp/scale-csv'; @mkdir($dir, 0777, true);
            $file = $dir.'/c'.$ci.'-e'.$exam->id.'.csv';
            $fh = fopen($file, 'w'); fputcsv($fh, ['KLS number', 'student_name', 'Mark']);
            foreach ($learners as $li => $learner) {
                $r = mt_rand(0, 99);
                $value = $r < 4 ? '' : ($r < 6 ? 'ABS' : (string) mt_rand(0, 100));
                fputcsv($fh, [$learner->registration_number, $learner->name, $value]);
            }
            fclose($fh);
            $t = microtime(true);
            $plan = $importer->preview($exam, $admin, $file, basename($file));
            $importer->commit($plan, $admin, overwrite: true);
            foreach ($learners as $learner) {
                if (! Marks::where('exam_id', $exam->id)->where('student_id', $learner->id)->exists()) {
                    $writer->save($admin, $exam, (int) $learner->id, 'AB', 'Scale rehearsal blank/ABS');
                }
            }
            $importTimes[] = microtime(true) - $t;
        }
    }
    $mark('imports_'.count($importTimes).'_exams', array_sum($importTimes));
    say('import avg '.round(array_sum($importTimes) / max(1, count($importTimes)), 3).'s max '.round(max($importTimes), 3).'s');
}

if (in_array($phase, ['all', 'release'], true)) {
    Auth::login(User::find($state['admin_id']));
    $school = School::find($state['school_id']);
    $term = AcademicTerm::find($state['term_id']);
    $admin = User::find($state['admin_id']);
    $teacher = User::find($state['teacher_id']);
    $submissions = app(MarksSubmissionService::class);
    $releases = app(MarksReleaseService::class);
    $t = microtime(true);
    foreach ($state['classes'] as $class) {
        if (\App\Models\MarksRelease::where('school_id', $school->id)->where('standard_link_id', $class['link_id'])->exists()) {
            say('class already released: '.$class['link_id']);
            continue;
        }
        $exams = Exam::where('school_id', $school->id)->where('academic_term_id', $term->id)->where('section_id', $class['section_id'])->get();
        foreach ($exams as $exam) {
            try { $submissions->submit($exam, $teacher); } catch (\Throwable $e) { say('submit note '.$exam->id.': '.$e->getMessage()); fflush(STDOUT); }
            try { $submissions->approve($admin, $exam); } catch (\Throwable $e) { say('approve fail '.$exam->id.': '.$e->getMessage()); fflush(STDOUT); }
        }
        $release = $releases->create($admin, (int) $exams->first()->id, (int) $class['link_id'], null, 'Africa/Kampala', true, false);
        $state['release_ids'][] = $release->id;
    }
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    $mark('release_10_classes', microtime(true) - $t);
}

if (in_array($phase, ['all', 'render'], true)) {
    $school = School::find($state['school_id']);
    $term = AcademicTerm::find($state['term_id']);
    $service = app(StudentReportCardService::class);
    $tAll = microtime(true);
    $perLearner = [];
    $perClass = [];
    $dir = '/tmp/scale-cards'; @mkdir($dir, 0777, true);
    foreach ($state['classes'] as $ci => $class) {
        $link = StandardLink::find($class['link_id']);
        $learners = User::where('school_id', $school->id)->where('usergroup_id', 6)
            ->whereHas('studentAcademic', fn ($q) => $q->where('standardLink_id', $class['link_id']))->orderBy('id')->get();
        $t = microtime(true);
        foreach ($learners as $learner) {
            $t0 = microtime(true);
            $pdf = $service->pdfForStudent((int) $school->id, $link, $learner, null, (int) $term->id);
            file_put_contents($dir.'/c'.$ci.'-'.$learner->id.'.pdf', $pdf);
            $perLearner[] = microtime(true) - $t0;
        }
        $t1 = microtime(true);
        $merged = $service->mergedPdfForLearners((int) $school->id, $link, $learners, (int) $term->id);
        file_put_contents($dir.'/class-'.$ci.'.pdf', $merged);
        $perClass[] = ['learners' => $learners->count(), 'seconds' => round(microtime(true) - $t1, 2), 'bytes' => strlen($merged)];
        say('class '.$ci.' cards '.$learners->count().' in '.round(microtime(true) - $t, 1).'s');
    }
    $state['render'] = [
        'cards' => count($perLearner),
        'per_card_avg' => round(array_sum($perLearner) / count($perLearner), 3),
        'per_card_max' => round(max($perLearner), 3),
        'per_class' => $perClass,
        'all_seconds' => round(microtime(true) - $tAll, 1),
    ];
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
    $mark('render_'.$state['render']['cards'].'_cards', microtime(true) - $tAll);
}
