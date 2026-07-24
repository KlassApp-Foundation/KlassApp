<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Race-safe generator for KlassApp student IDs (KLS{school_id_3}{seq_4}).
 *
 * Uses a dedicated student_id_sequences table with row-level locking
 * (SELECT ... FOR UPDATE inside a transaction) to guarantee uniqueness
 * across concurrent calls, even across different processes.
 *
 * Before first use in a production environment, the sequence table must
 * be seeded per school via SeedStudentIdSequences command — see
 * php artisan students:seed-sequences.
 */
class StudentIdGeneratorService
{
    /**
     * Atomically reserve and return the next klassapp_student_id for a school.
     *
     * Format: KLS{school_id_padded_to_3}{seq_padded_to_4}
     * Example: school_id=1, seq=1 → "KLS0010001".
     */
    public static function next(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId) {
            // Lock the row (or the intent to insert) so no two calls get the
            // same sequence number, even concurrently.
            $row = DB::table('student_id_sequences')
                ->where('school_id', $schoolId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                // First-ever call for this school — seed at seq 1, next call gets 2.
                DB::table('student_id_sequences')->insert([
                    'school_id' => $schoolId,
                    'next_seq'  => 2,
                ]);
                $seq = 1;
            } else {
                $seq = $row->next_seq;
                DB::table('student_id_sequences')
                    ->where('school_id', $schoolId)
                    ->update(['next_seq' => $seq + 1]);
            }

            return sprintf('KLS%03d%04d', $schoolId, $seq);
        });
    }
}