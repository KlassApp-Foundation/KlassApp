<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ReportCardsController;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ReportCardCommentService;
use App\Services\StudentReportHelperService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Build-time / CI tool only — never invoked at request time. Renders each
 * registered report-card template against synthetic, tenant-agnostic sample
 * data (never a real school's data, since the resulting image is shown to
 * every school on the platform), rasterizes page 1 via the `pdftoppm`
 * binary (poppler-utils), and writes a static PNG to
 * public/images/report-template-thumbnails/. The admin template picker
 * serves these as plain <img> tags — no per-request PDF rendering.
 *
 * Re-run and commit the updated PNGs whenever a template's Blade/CSS
 * changes. Requires poppler-utils (`pdftoppm`) on the machine running the
 * command; not a production runtime dependency.
 */
class GenerateReportTemplateThumbnails extends Command
{
    protected $signature = 'reports:generate-template-thumbnails {--only=}';
    protected $description = 'Render a static PNG thumbnail per report-card template from synthetic sample data';

    public function handle(): int
    {
        if (!$this->binaryAvailable('pdftoppm')) {
            $this->error('pdftoppm (poppler-utils) not found on PATH. Install poppler-utils and re-run.');
            return self::FAILURE;
        }

        $outDir = public_path('images/report-template-thumbnails');
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $only = $this->option('only');
        $templates = $only
            ? array_intersect_key(ReportCardsController::TEMPLATES, [$only => true])
            : ReportCardsController::TEMPLATES;

        if (empty($templates)) {
            $this->error('No matching template(s) to render.');
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            // Pre-existing, unrelated bug: Events model is missing 'batch'/'color'
            // from $fillable while both columns are NOT NULL with no default,
            // which breaks Exam::syncCalendarEvents() for any exam creation.
            // Unguard only for this command's synthetic fixture so seeding
            // doesn't fatal; not a fix for the underlying issue.
            \App\Models\Events::unguard();

            [$aaron, $eot, $stdLink, $schoolId] = $this->seedSampleFixture();

            $helper = app(StudentReportHelperService::class);
            $svc = app(ReportCardCommentService::class);
            $totalLearners = Marks::where('exam_id', $eot->id)->distinct('student_id')->count();

            foreach ($templates as $key => $tpl) {
                $this->info("Rendering {$tpl['label']} ({$key})...");

                $pdf = ReportCardsController::generatePdf($aaron->id, $eot, $stdLink, $schoolId, $helper, $svc, $totalLearners, 2, $key);

                $tmpPdf = tempnam(sys_get_temp_dir(), 'thumb') . '.pdf';
                file_put_contents($tmpPdf, $pdf);

                $tmpPrefix = sys_get_temp_dir() . '/report-thumb-' . $key;
                // Crop to the top ~82% of the page (header through the
                // signature block) — the remainder is blank page padding
                // that adds nothing to a picker thumbnail.
                $cmd = sprintf(
                    'pdftoppm -png -r 120 -f 1 -l 1 -x 0 -y 0 -W 993 -H 1150 %s %s 2>&1',
                    escapeshellarg($tmpPdf),
                    escapeshellarg($tmpPrefix)
                );
                exec($cmd, $output, $exitCode);
                @unlink($tmpPdf);

                $rendered = $tmpPrefix . '-1.png';
                if ($exitCode !== 0 || !file_exists($rendered)) {
                    $this->error("  pdftoppm failed for {$key}: " . implode("\n", $output));
                    continue;
                }

                $dest = $outDir . "/{$key}.png";
                rename($rendered, $dest);
                $this->info("  Wrote {$dest}");
            }
        } finally {
            DB::rollBack();
        }

        return self::SUCCESS;
    }

    private function binaryAvailable(string $bin): bool
    {
        $which = trim((string) shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null'));
        return $which !== '';
    }

    /**
     * Synthetic, tenant-agnostic sample data — deliberately not any real
     * school's data, since the resulting thumbnail is shown to every school
     * on the platform as a style preview, not a real report.
     *
     * @return array{0: User, 1: Exam, 2: StandardLink, 3: int}
     */
    private function seedSampleFixture(): array
    {
        $suffix = Str::random(6);

        $school = School::create([
            'name' => 'Sample Academy', 'email' => "thumbnail-{$suffix}@example.test",
            'phone' => '+2560000' . random_int(100000, 999999), 'address' => '123 Sample Road, Sample Town',
            'status' => 1,
        ]);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => date('Y'), 'start_date' => date('Y') . '-02-01', 'end_date' => date('Y') . '-11-30', 'status' => 1]);
        $term = AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 2', 'starts_on' => now()->subMonths(2), 'ends_on' => now(), 'status' => 'current']);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 2, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5 A', 'status' => 1]);

        $teacher = User::create(['school_id' => $school->id, 'usergroup_id' => 5, 'name' => 'sample teacher', 'email' => "teacher-{$suffix}@example.test", 'password' => Hash::make(Str::random(24)), 'email_verified' => 1]);
        Userprofile::create(['school_id' => $school->id, 'user_id' => $teacher->id, 'usergroup_id' => 5, 'firstname' => 'Sample', 'lastname' => 'Teacher', 'status' => 'active']);

        $stdLink = StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'no_of_students' => 3, 'status' => 1]);

        $bands = [
            ['grade' => 'D1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
            ['grade' => 'D2', 'points' => 2, 'min_score' => 85, 'max_score' => 94, 'remark' => 'V.Good'],
            ['grade' => 'D3', 'points' => 3, 'min_score' => 75, 'max_score' => 84, 'remark' => 'Good'],
            ['grade' => 'D4', 'points' => 4, 'min_score' => 65, 'max_score' => 74, 'remark' => 'F.Good/Q.Good'],
            ['grade' => 'D5', 'points' => 5, 'min_score' => 60, 'max_score' => 64, 'remark' => 'Promising'],
            ['grade' => 'D6', 'points' => 6, 'min_score' => 50, 'max_score' => 59, 'remark' => 'Fair'],
            ['grade' => 'D7', 'points' => 7, 'min_score' => 45, 'max_score' => 49, 'remark' => 'Work hard'],
            ['grade' => 'D8', 'points' => 8, 'min_score' => 40, 'max_score' => 44, 'remark' => 'Aim higher'],
            ['grade' => 'D9', 'points' => 9, 'min_score' => 0, 'max_score' => 39, 'remark' => 'More effort'],
        ];
        foreach ($bands as $b) {
            SchoolGradingSystem::create(array_merge(['school_id' => $school->id, 'standard_id' => $standard->id], $b));
        }

        $subjectNames = ['ENGLISH', 'MATHEMATICS', 'SCIENCE', 'SOCIAL STUDIES'];
        $subjects = [];
        foreach ($subjectNames as $name) {
            $subjects[$name] = Subject::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'name' => $name, 'type' => 'core']);
        }
        foreach ($subjectNames as $name) {
            Teacherlink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standardLink_id' => $stdLink->id, 'subject_id' => $subjects[$name]->id, 'teacher_id' => $teacher->id]);
        }

        $midType = ExamType::create(['name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => false]);
        $eotType = ExamType::create(['name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $firstSubjectId = $subjects[$subjectNames[0]]->id;

        $june = Exam::create(['standard_id' => $standard->id, 'school_id' => $school->id, 'academic_year_id' => $year->id, 'subject_id' => $firstSubjectId, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'academic_term_id' => $term->id, 'exam_type_id' => $midType->id, 'scheduled_at' => now()->subMonths(2), 'status' => 'done']);
        $eot = Exam::create(['standard_id' => $standard->id, 'school_id' => $school->id, 'academic_year_id' => $year->id, 'subject_id' => $firstSubjectId, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'academic_term_id' => $term->id, 'exam_type_id' => $eotType->id, 'scheduled_at' => now(), 'status' => 'done']);

        $gradeFor = function (int $mark) use ($bands): string {
            foreach ($bands as $b) {
                if ($mark >= $b['min_score'] && $mark <= $b['max_score']) return $b['grade'];
            }
            return '';
        };

        $makeStudent = function (string $fn, string $ln, int $juneTotal, int $eotTotal) use ($school, $year, $stdLink, $subjects, $teacher, $section, $june, $eot, $gradeFor, $suffix) {
            $u = User::create(['school_id' => $school->id, 'usergroup_id' => 6, 'name' => strtolower("$fn $ln"), 'email' => strtolower("$fn.$ln-{$suffix}") . '@example.test', 'password' => Hash::make(Str::random(24)), 'email_verified' => 1]);
            Userprofile::create(['school_id' => $school->id, 'user_id' => $u->id, 'usergroup_id' => 6, 'firstname' => $fn, 'lastname' => $ln, 'gender' => 'female', 'status' => 'active']);
            StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'user_id' => $u->id, 'standardLink_id' => $stdLink->id]);

            $names = array_keys($subjects);
            foreach ([[$june, $juneTotal], [$eot, $eotTotal]] as [$exam, $total]) {
                $n = count($names);
                $base = intdiv($total, $n);
                $marks = array_fill(0, $n, $base);
                $marks[$n - 1] += $total - array_sum($marks);
                foreach ($names as $i => $name) {
                    $m = $marks[$i];
                    Marks::create(['student_id' => $u->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id, 'subject_id' => $subjects[$name]->id, 'exam_id' => $exam->id, 'section_id' => $section->id, 'marks' => $m, 'grade' => $gradeFor($m)]);
                }
            }

            return $u;
        };

        $jane = $makeStudent('Jane', 'Sample', 260, 300);
        $makeStudent('John', 'Doe', 220, 260);
        $makeStudent('Amy', 'Rose', 200, 230);

        return [$jane, $eot, $stdLink, $school->id];
    }
}
