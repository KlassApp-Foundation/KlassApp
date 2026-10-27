<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe generator for KlassApp student IDs.
 *
 * Historical IDs are KLS plus 7 digits (school id padded to 3, sequence padded
 * to 4): school 1, sequence 1 is KLS0010001. Sequence 10000 no longer fits in
 * 4 digits, so the digit run grows (KLS00110000) and stays valid. Stored IDs
 * are never rewritten.
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
     * KLS plus 7 to 16 digits. Seven digits is every ID issued before a school
     * passed 9,999 students. Longer values are the same generator once the
     * sequence needs another digit.
     */
    public const PATTERN = '/^KLS\d{7,16}$/i';

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Atomically reserve and return the next klassapp_student_id for a school.
     *
     * Format: KLS{school_id_padded_to_3}{seq_padded_to_at_least_4}
     * Example: school_id=1, seq=1 → "KLS0010001". seq=10000 → "KLS00110000".
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

    /**
     * The student's KLS number, assigning one only when the student does not
     * have one yet. Never reassigns: an existing value is returned untouched,
     * so re-runs and concurrent callers cannot corrupt a student's identity.
     */
    public static function ensureForStudent(User $student): string
    {
        $existing = trim((string) $student->registration_number);
        if ($existing !== '') {
            return $existing;
        }

        return self::nextForStudent($student);
    }
}