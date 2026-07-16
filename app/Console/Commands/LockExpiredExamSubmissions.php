<?php

namespace App\Console\Commands;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LockExpiredExamSubmissions extends Command
{
    protected $signature = 'marks:lock-expired
                           {--exam-id= : Lock only submissions for a specific exam (used in tests)}
                           {--dry-run : Log what would be locked without locking}';

    protected $description = 'Transition exam_marks_submissions past their deadline into locked status';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificExamId = $this->option('exam-id');
        $locked = 0;
        $created = 0;

        // 1. Lock existing submissions that are past their deadline
        $query = ExamMarksSubmission::lockable();
        if ($specificExamId) {
            $query->where('exam_id', $specificExamId);
        }

        $expired = $query->get();

        foreach ($expired as $submission) {
            if ($dryRun) {
                $this->line("[DRY-RUN] Would lock exam_marks_submission #{$submission->id} (exam:{$submission->exam_id})");
                continue;
            }
            $submission->status = 'locked';
            $submission->locked_at = now();
            $submission->approval_status = 'pending';
            $submission->save();
            $locked++;
        }

        // 2. Handle exams where some class/subject combinations have NO submission row
        //    but the exam's default_deadline has passed. Create locked rows so admins
        //    can see incomplete submissions rather than a silent gap.
        $examQuery = Exam::query()->whereNotNull('default_deadline')
            ->where('default_deadline', '<=', now());

        if ($specificExamId) {
            $examQuery->where('id', $specificExamId);
        }

        $exams = $examQuery->get();

        foreach ($exams as $exam) {
            // Determine which class/subject combinations exist for this exam
            $expected = \Illuminate\Support\Facades\DB::table('marks')
                ->where('exam_id', $exam->id)
                ->selectRaw('DISTINCT subject_id, section_id as class_id')
                ->get();

            // Add all combos found in the marks table (section_id = class)
            $expectedCombos = collect();
            foreach ($expected as $row) {
                $expectedCombos->push([
                    'class_id'   => $row->class_id,
                    'subject_id' => $row->subject_id,
                ]);
            }
            // Also add the exam's own section/subject as a minimum expected pair
            // (use section_id — NOT standard_id — since class_id FK points to sections)
            $expectedCombos->push([
                'class_id'   => $exam->section_id,
                'subject_id' => $exam->subject_id,
            ]);
            $expectedCombos = $expectedCombos->unique(function ($item) {
                return $item['class_id'] . '-' . $item['subject_id'];
            });

            foreach ($expectedCombos as $combo) {
                $exists = ExamMarksSubmission::where('exam_id', $exam->id)
                    ->where('class_id', $combo['class_id'])
                    ->where('subject_id', $combo['subject_id'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would create locked submission for exam {$exam->id}, class {$combo['class_id']}, subject {$combo['subject_id']}");
                    continue;
                }

                ExamMarksSubmission::create([
                    'school_id'  => $exam->school_id,
                    'exam_id'    => $exam->id,
                    'class_id'   => $combo['class_id'],
                    'subject_id' => $combo['subject_id'],
                    'teacher_id' => $exam->teacher_id,
                    'status'     => 'locked',
                    'deadline'   => $exam->default_deadline,
                    'locked_at'  => now(),
                ]);
                $created++;
            }
        }

        $summary = "Locked {$locked} existing submission(s), created {$created} locked row(s) for incomplete submissions.";
        $this->info($summary);
        Log::info("LockExpiredExamSubmissions: {$summary}");

        return 0;
    }
}
