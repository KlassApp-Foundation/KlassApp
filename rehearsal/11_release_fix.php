<?php
require __DIR__.'/boot.php';
use App\Models\{AcademicTerm, MarksRelease, School, User};
use App\Models\Academics\Exam;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Support\Facades\Auth;

$s = json_decode(file_get_contents(__DIR__.'/scale-state.json'), true);
$school = School::find($s['school_id']); $term = AcademicTerm::find($s['term_id']);
$admin = User::find($s['admin_id']); $teacher = User::find($s['teacher_id']);
Auth::login($admin);
$subs = app(MarksSubmissionService::class); $rel = app(MarksReleaseService::class);
$releases = [];
foreach ($s['classes'] as $i => $class) {
    if (MarksRelease::where('school_id', $school->id)->where('standard_link_id', $class['link_id'])->exists()) { echo "class $i already released\n"; continue; }
    $exams = Exam::where('school_id', $school->id)->where('academic_term_id', $term->id)->where('section_id', $class['section_id'])->orderBy('id')->get();
    foreach ($exams as $exam) {
        try { $subs->submit($exam, $teacher); } catch (\Throwable $e) {}
        try { $subs->approve($admin, $exam); } catch (\Throwable $e) { echo "approve ".$exam->id." fail: ".$e->getMessage()."\n"; }
    }
    $sitting = $rel->exams($admin)->first(fn ($row) => in_array((int) $class['link_id'], $row['link_ids'], true));
    if (! $sitting) { echo "class $i: no sitting lists this class\n"; continue; }
    try {
        $r = $rel->create($admin, (int) $sitting['id'], (int) $class['link_id'], null, 'Africa/Kampala', true, false);
        $releases[] = $r->id;
        echo "class $i release ".$r->id." anchor ".$sitting['id']."\n";
    } catch (\Throwable $e) { echo "class $i RELEASE FAIL: ".$e->getMessage()."\n"; }
}
$s['release_ids'] = $releases;
file_put_contents(__DIR__.'/scale-state.json', json_encode($s, JSON_PRETTY_PRINT));
