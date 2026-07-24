<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedStudentIdSequences extends Command
{
    protected $signature = 'students:seed-sequences
        {--dry-run : Report what would be seeded without writing}';

    protected $description = 'Seed the student_id_sequences table from existing klassapp_student_id values';

    public function handle()
    {
        $schools = DB::table('student_academics')
            ->select('school_id')
            ->whereNotNull('klassapp_student_id')
            ->where('klassapp_student_id', 'like', 'KLS%')
            ->distinct()
            ->pluck('school_id');

        $seeded = 0;
        foreach ($schools as $schoolId) {
            $maxSeq = DB::table('student_academics')
                ->where('school_id', $schoolId)
                ->whereNotNull('klassapp_student_id')
                ->where('klassapp_student_id', 'like', 'KLS%')
                ->selectRaw('MAX(CAST(SUBSTRING(klassapp_student_id, 7) AS UNSIGNED)) as max_seq')
                ->value('max_seq');

            $maxSeq = $maxSeq ?: 0;
            $nextSeq = $maxSeq + 1;

            if ($this->option('dry-run')) {
                $this->line("school_id={$schoolId}: next_seq would be {$nextSeq} (max existing seq={$maxSeq})");
            } else {
                DB::table('student_id_sequences')->updateOrInsert(
                    ['school_id' => $schoolId],
                    ['next_seq' => $nextSeq]
                );
                $this->line("school_id={$schoolId}: next_seq set to {$nextSeq}");
            }
            $seeded++;
        }

        if ($seeded === 0) {
            $this->warn('No schools with existing KLS-style klassapp_student_id values found. Nothing to seed.');
        }

        $this->info("Done. {$seeded} school(s) processed.");
    }
}