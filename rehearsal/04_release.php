<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, School, StandardLink, User};
use App\Models\Academics\{Exam, Marks};
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Support\Facades\Auth;

$s = state();
$school = School::findOrFail($s['school_id']);
$term = AcademicTerm::findOrFail($s['term_id']);
$admin = User::findOrFail($s['admin_id']);
$teacher = User::findOrFail($s['teacher_id']);
$link = StandardLink::findOrFail($s['link_id']);
Auth::login($admin);

$exams = Exam::query()->where('school_id', $school->id)->where('academic_term_id', $term->id)->orderBy('id')->get();
$roster = User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->where('status', 'active')->pluck('id');
$writer = app(ExamMarkWriter::class);

$filled = [];
foreach ($exams as $exam) {
    $have = Marks::query()->where('exam_id', $exam->id)->where('school_id', $school->id)->pluck('student_id')->map(fn ($id) => (int) $id)->all();
    $missing = $roster->diff($have);
    foreach ($missing as $studentId) {
        $writer->save($admin, $exam, (int) $studentId, 'AB', 'Rehearsal: blank/ABS in the source sheet');
        $filled[] = $exam->id.':'.$studentId;
    }
}
say('cells filled as AB: '.count($filled).' ('.implode(', ', array_slice($filled, 0, 6)).')');
$s['filled_ab'] = $filled;

$submissions = app(MarksSubmissionService::class);
foreach ($exams as $exam) {
    try {
        $submissions->submit($exam, $teacher);
    } catch (\Throwable $e) {
        say('SUBMIT FAILED exam '.$exam->id.': '.$e->getMessage());
        continue;
    }
    $submissions->approve($admin, $exam);
}
say('submissions: '.\App\Models\Academics\ExamMarksSubmission::where('school_id', $school->id)->count().' approved='.\App\Models\Academics\ExamMarksSubmission::where('school_id', $school->id)->where('approval_status', 'approved')->count());

$release = app(MarksReleaseService::class)->create($admin, (int) $exams->first()->id, (int) $link->id, null, 'Africa/Kampala', true, false);
say('release '.$release->id.' status '.$release->status.' scope '.$release->scope);
$s['release_id'] = $release->id;
save_state($s);
