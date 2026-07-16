<?php

namespace App\Console\Commands;

use App\Models\Academics\Exam;
use Illuminate\Console\Command;

class ExamsSyncCalendar extends Command
{
    protected $signature = 'exams:sync-calendar';
    protected $description = 'Create calendar events for all exams that lack them (one-time backfill)';

    public function handle(): int
    {
        $exams = Exam::whereDoesntHave('calendarEvents')->get();
        $count = 0;

        foreach ($exams as $exam) {
            $exam->syncCalendarEvents();
            $count++;
        }

        $this->components->info("Synced {$count} exam(s) to the calendar.");

        return self::SUCCESS;
    }
}
