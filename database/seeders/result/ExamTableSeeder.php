<?php

namespace Database\Seeders\result;

use App\Models\Standard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;

class ExamTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where("status", 1)->get();

        if ($schools->isEmpty()) {
            $this->command->warn('No active schools. Skipping exams.');
            return;
        }

        $examTemplates = [
            // Pre-primary / lower primary (lighter)
            ['type' => 'Continuous Assessment 1', 'offset_days' => 30, 'for_levels' => range(1, 7)],   // P1–P7 order
            ['type' => 'Continuous Assessment 2', 'offset_days' => 60, 'for_levels' => range(1, 7)],
            ['type' => 'End-of-Term Exam',        'offset_days' => 90, 'for_levels' => range(1, 10)],

            // Upper primary & secondary
            ['type' => 'Mid-Term Exam',    'offset_days' => 45,  'for_levels' => range(8, 16)],
            ['type' => 'End-of-Term Exam', 'offset_days' => 100, 'for_levels' => range(8, 16)],
            ['type' => 'Mock Exam',        'offset_days' => 180, 'for_levels' => [14,15,16]], // S4,S5,S6
        ];

        $seeded = 0;

        foreach ($schools as $school) {
            $years = AcademicYear::where('school_id', $school->id)
                ->where('status', 1)
                ->latest()
                ->take(2)           // last 1–2 years only
                ->get();

            if ($years->isEmpty()) continue;

            $teachers = User::where('school_id', $school->id)
                ->where('usergroup_id', 5) // teachers
                ->inRandomOrder()
                ->limit(12)
                ->get();

            if ($teachers->isEmpty()) continue;

            foreach ($years as $year) {
                // Get class-rooms (standard + academic_year combos)
                $classRooms = StandardLink::where('school_id', $school->id)
                    ->where('academic_year_id', $year->id)
                    ->get(['standard_id']);

                if ($classRooms->isEmpty()) continue;

                foreach ($classRooms as $cr) {
                    $standardOrder = Standard::find($cr->standard_id)?->order ?? 99;

                    // Subjects for this standard (you may have a relation or pivot)
                    $subjects = Subject::where('school_id', $school->id)
                        ->whereJsonContains('standards', $standardOrder) // if you store allowed standards in json
                        ->orWhere('standard_id', $cr->standard_id)       // if per subject
                        ->limit(8) // don't seed every subject
                        ->get();

                    foreach ($subjects as $subject) {
                        // Pick 1–3 exam types realistic for this level
                        $possibleExams = collect($examTemplates)
                            ->whereIn('for_levels', $standardOrder)
                            ->random(rand(1, 3));

                        foreach ($possibleExams as $tmpl) {
                            $examDate = Carbon::parse($year->start_date)
                                ->addDays($tmpl['offset_days'] + rand(-10, 10));

                            // Pick one teacher (main subject teacher simulation)
                            $teacher = $teachers->random();

                            DB::table('exams')->updateOrInsert(
                                [
                                    'school_id'        => $school->id,
                                    'academic_year_id' => $year->id,
                                    'standard_id'      => $cr->standard_id,
                                    'subject_id'       => $subject->id,
                                    'type'             => $tmpl['type'],
                                ],
                                [
                                    'teacher_id'    => $teacher->id,
                                    'scheduled_at'  => $examDate,
                                    'term'          => $this->guessTerm($examDate, $year), // optional helper
                                    'status'        => true,
                                    'created_at'    => now(),
                                    'updated_at'    => now(),
                                ]
                            );

                            $seeded++;
                        }
                    }
                }
            }

            $this->command->info("Seeded ~$seeded exams for {$school->name}");
        }

        $this->command->info("Total realistic exams seeded: $seeded");
    }

    private function guessTerm(Carbon $date, AcademicYear $year): int
    {
        $daysIn = $date->diffInDays($year->start_date);
        if ($daysIn < 60) return 1;
        if ($daysIn < 120) return 2;
        return 3;
    }
}