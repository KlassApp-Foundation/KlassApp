<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe generator for KlassApp student IDs.
 *
 * School id up to 999 and sequence up to 9999 keep the historical form:
 * KLS plus school padded to 3 digits plus sequence padded to 4 (KLS0010005).
 * Anything past either limit uses KLS plus school padded to 5 digits plus
 * sequence padded to 6 (KLS00100010001, KLS01001000001). The two lengths
 * cannot collide. A school or sequence that does not fit in 5 and 6 digits
 * is refused. Stored IDs are never rewritten.
 *
 * PATTERN accepts exactly 7 or 11 digits after KLS. #1244 WhatsAppController
 * still matches only /^KLS\d{7}$/i until it adopts this constant.
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
     * KLS plus exactly 7 digits, or KLS plus exactly 11 digits. No hyphens.
     * #1244's WhatsApp matcher should adopt this constant after this PR merges.
     */
    public const PATTERN = '/^KLS(?:\d{7}|\d{11})$/i';

    /**
     * HTML pattern attribute. Same shape as PATTERN, without the delimiters.
     */
    public const HTML_PATTERN = 'KLS(?:\d{7}|\d{11})';

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Atomically reserve and return the next klassapp_student_id for a school.
     *
     * School 1, sequence 5 is KLS0010005. School 100, sequence 10001 is
     * KLS00100010001, which is not school 1001's KLS01001000001.
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

            $seq = $row ? (int) $row->next_seq : 1;
            if ($seq < 1) {
                $seq = 1;
            }

            $id = null;
            for ($attempt = 0; $attempt < 1000; $attempt++) {
                $candidate = self::format($schoolId, $seq);
                if (! self::isTaken($candidate)) {
                    $id = $candidate;
                    break;
                }
                $seq++;
            }

            if ($id === null) {
                throw new \RuntimeException('Could not reserve a unique student ID for school '.$schoolId.'.');
            }

            if (! $row) {
                DB::table('student_id_sequences')->insert([
                    'school_id' => $schoolId,
                    'next_seq' => $seq + 1,
                ]);
            } else {
                DB::table('student_id_sequences')
                    ->where('school_id', $schoolId)
                    ->update(['next_seq' => $seq + 1]);
            }

            return $id;
        });
    }

    public static function format(int $schoolId, int $sequence): string
    {
        if ($schoolId < 0 || $sequence < 0) {
            throw new \InvalidArgumentException(
                "Student ID school and sequence must be zero or greater (school {$schoolId}, sequence {$sequence})."
            );
        }

        if ($schoolId <= 999 && $sequence <= 9999) {
            return sprintf('KLS%03d%04d', $schoolId, $sequence);
        }

        if ($schoolId > 99999 || $sequence > 999999) {
            throw new \InvalidArgumentException(
                "Student ID does not fit KLS plus 5 school digits and 6 sequence digits (school {$schoolId}, sequence {$sequence})."
            );
        }

        return sprintf('KLS%05d%06d', $schoolId, $sequence);
    }

    private static function isTaken(string $id): bool
    {
        if (DB::table('users')->where('registration_number', $id)->exists()) {
            return true;
        }

        return DB::table('student_academics')->where('klassapp_student_id', $id)->exists();
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