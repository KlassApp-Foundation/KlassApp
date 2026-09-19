<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe generator for KlassApp student IDs (KA{seq_8}).
 *
 * Uses a dedicated student_id_sequences table with row-level locking
 * (SELECT ... FOR UPDATE inside a transaction) to guarantee uniqueness
 * across concurrent calls, even across different processes.
 *
 * Convention: users.registration_number and student_academics.klassapp_student_id
 * share the same generated value (see BackfillRegistrationNumbers).
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
    * Format: KA{seq_padded_to_8}. The sequence remains school-scoped while
    * the visible identifier stays consistent across schools.
     */
    public static function next(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId) {
            // Lock all sequence rows because the visible KA number is global,
            // not school-prefixed. This prevents two schools reserving the same ID.
            $rows = DB::table('student_id_sequences')->lockForUpdate()->get();
            $row = $rows->firstWhere('school_id', $schoolId);
            $seq = (int) ($rows->max('next_seq') ?? 1);

            $existingIds = DB::table('student_academics')
                ->whereNotNull('klassapp_student_id')
                ->pluck('klassapp_student_id');
            foreach ($existingIds as $existingId) {
                if (preg_match('/^KA(\d{8})$/', (string) $existingId, $matches)) {
                    $seq = max($seq, (int) $matches[1] + 1);
                }
            }

            if (! $row) {
                DB::table('student_id_sequences')->insert([
                    'school_id' => $schoolId,
                    'next_seq'  => $seq + 1,
                ]);
            } else {
                DB::table('student_id_sequences')
                    ->where('school_id', $schoolId)
                    ->update(['next_seq' => $seq + 1]);
            }

            return sprintf('KA%08d', $seq);
        });
    }

    /**
     * Reserve the next KLS ID and persist it on users.registration_number.
     *
     * Callers must also store the returned value on
     * student_academics.klassapp_student_id so both fields stay in sync.
     */
    public static function nextForStudent(User $student): string
    {
        $id = self::next((int) $student->school_id);
        $student->forceFill(['registration_number' => $id])->save();

        return $id;
    }
}