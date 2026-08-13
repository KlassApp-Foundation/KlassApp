<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Apply authoritative roster (Final_Merged_KJS_List.xlsx) stream corrections for
 * school 104, academic year 51.
 *
 * The roster is the school's declared single source of truth. 70 currently-enrolled
 * students were matched by name and found on a standards_link whose stream disagrees
 * with the roster. Each mapping below moves the student to the sibling standards_link
 * for the same class (same section_id) with the correct stream.
 *
 * Safety: only same-section moves are embedded (cross-section/cross-class cases were
 * flagged for review instead). The up() guard re-asserts the section match before
 * writing, so a stale uid can never silently change a student's class.
 *
 * Down: returns each moved student to the link they were on before this migration.
 */
return new class extends Migration
{
    private const ACADEMIC_YEAR_ID = 51;

    /** @var array<int, int> user_id => target standards_link id (same section only) */
    private const MAPPING = [
        2686 => 83,
        2711 => 83,
        2779 => 84,
        2793 => 84,
        2795 => 84,
        2797 => 84,
        2808 => 84,
        2843 => 55,
        2895 => 85,
        2896 => 85,
        2899 => 85,
        2900 => 85,
        2916 => 85,
        2919 => 85,
        2925 => 85,
        2930 => 85,
        2932 => 85,
        2934 => 85,
        2936 => 85,
        2938 => 85,
        2940 => 86,
        2941 => 86,
        2942 => 86,
        2944 => 86,
        2946 => 86,
        2951 => 86,
        2952 => 86,
        2953 => 86,
        2957 => 86,
        2959 => 86,
        2963 => 86,
        2965 => 86,
        3045 => 86,
        3047 => 86,
        3049 => 86,
        3052 => 86,
        3053 => 86,
        3054 => 86,
        3061 => 86,
        3062 => 86,
        3065 => 86,
        3066 => 86,
        3068 => 86,
        3071 => 86,
        3072 => 86,
        3073 => 86,
        3077 => 86,
        3078 => 86,
        3090 => 87,
        3092 => 87,
        3094 => 87,
        3097 => 87,
        3100 => 87,
        3109 => 87,
        3167 => 87,
        3168 => 87,
        3170 => 87,
        3171 => 87,
        3175 => 87,
        3176 => 87,
        3178 => 87,
        3185 => 87,
        3187 => 87,
        3189 => 87,
        3191 => 87,
        3192 => 87,
        3195 => 87,
        3196 => 87,
        3258 => 81,
        3259 => 81,
    ];

    public function up(): void
    {
        foreach (self::MAPPING as $uid => $targetLinkId) {
            $student = DB::table('student_academics')
                ->where('user_id', $uid)
                ->where('academic_year_id', self::ACADEMIC_YEAR_ID)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->first();

            if (! $student) {
                continue; // not enrolled in the target year anymore
            }

            $current = DB::table('standards_link')->where('id', $student->standardLink_id)->first();
            $target  = DB::table('standards_link')->where('id', $targetLinkId)->first();

            if (! $current || ! $target) {
                continue;
            }

            // Same-section guard: never move a student across classes.
            if ($current->section_id !== $target->section_id) {
                continue;
            }

            if ($student->standardLink_id === $targetLinkId) {
                continue; // already correct
            }

            DB::table('student_academics')
                ->where('id', $student->id)
                ->update(['standardLink_id' => $targetLinkId, 'updated_at' => now()]);
        }

        Cache::forget('standardLink104_51');
    }

    public function down(): void
    {
        foreach (self::MAPPING as $uid => $targetLinkId) {
            $student = DB::table('student_academics')
                ->where('user_id', $uid)
                ->where('academic_year_id', self::ACADEMIC_YEAR_ID)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->first();

            if (! $student || $student->standardLink_id !== $targetLinkId) {
                continue; // unchanged or already moved elsewhere
            }

            $target = DB::table('standards_link')->where('id', $targetLinkId)->first();

            if (! $target) {
                continue;
            }

            $original = DB::table('standards_link')
                ->where('school_id', $target->school_id)
                ->where('standard_id', $target->standard_id)
                ->where('section_id', $target->section_id)
                ->where('stream', $target->stream === 'EAST' ? 'WEST' : ($target->stream === 'WEST' ? 'EAST' : ($target->stream === 'A' ? 'B' : 'A')))
                ->first();

            if (! $original) {
                continue;
            }

            DB::table('student_academics')
                ->where('id', $student->id)
                ->update(['standardLink_id' => $original->id, 'updated_at' => now()]);
        }

        Cache::forget('standardLink104_51');
    }
};