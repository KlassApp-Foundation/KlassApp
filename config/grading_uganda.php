<?php

/**
 * MoE Uganda Grading Scales — config-driven, per standard level.
 *
 * Each level type maps to an array of grade definitions. Each definition:
 *   grade       => string label (e.g. "D1", "A", "Excellent")
 *   points      => int|null  (aggregate points for UACE-style systems; null for descriptive)
 *   min_score   => int       (inclusive lower bound, percentage)
 *   max_score   => int       (inclusive upper bound, percentage)
 *   remark      => string    (human-readable descriptor)
 *
 * The auto-selection logic (in GradingSystemService or a helper) reads the
 * student's standard -> determines level type -> loads the corresponding scale
 * from here -> seeds/updates SchoolGradingSystem records per school.
 *
 * == LEVEL TYPE DETECTION ==
 * Determined from the Standard record's name or a lookup:
 *   nursery   -> standards whose name indicates nursery (Baby Class, Middle Class, Top Class)
 *   primary   -> "Primary One" through "Primary Seven" (P1-P7)
 *   o-level   -> "Senior One" through "Senior Four" (S1-S4)
 *   a-level   -> "Senior Five" through "Senior Six" (S5-S6)
 *
 * See App\Helpers\GradingHelper::levelTypeForStandard() for the mapping logic.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | PRIMARY (P1-P7) — D1-F9 Percentage Scale
    |--------------------------------------------------------------------------
    | Traditional Ugandan primary school grading. Used for termly internal
    | school marks. NOT the PLE aggregate-points system (which is for
    | final national exam certification only, not termly reports).
    */
    'primary' => [
        ['grade' => 'D1', 'points' => null, 'min_score' => 85, 'max_score' => 100, 'remark' => 'Excellent'],
        ['grade' => 'D2', 'points' => null, 'min_score' => 80, 'max_score' => 84,  'remark' => 'Very Good'],
        ['grade' => 'C3', 'points' => null, 'min_score' => 75, 'max_score' => 79,  'remark' => 'Good'],
        ['grade' => 'C4', 'points' => null, 'min_score' => 70, 'max_score' => 74,  'remark' => 'Fairly Good'],
        ['grade' => 'C5', 'points' => null, 'min_score' => 65, 'max_score' => 69,  'remark' => 'Average'],
        ['grade' => 'C6', 'points' => null, 'min_score' => 60, 'max_score' => 64,  'remark' => 'Below Average'],
        ['grade' => 'P7', 'points' => null, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Pass'],
        ['grade' => 'P8', 'points' => null, 'min_score' => 40, 'max_score' => 49,  'remark' => 'Weak Pass'],
        ['grade' => 'F9', 'points' => null, 'min_score' => 0,  'max_score' => 39,  'remark' => 'Fail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | O-LEVEL (S1-S4) — A-E Letter Grades (NLSC/UCE system)
    |--------------------------------------------------------------------------
    | Uganda's New Lower Secondary Curriculum grading used for all
    | O-Level termly assessments.
    */
    'o-level' => [
        ['grade' => 'A', 'points' => null, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent'],
        ['grade' => 'B', 'points' => null, 'min_score' => 70, 'max_score' => 79,  'remark' => 'Very Good'],
        ['grade' => 'C', 'points' => null, 'min_score' => 60, 'max_score' => 69,  'remark' => 'Good'],
        ['grade' => 'D', 'points' => null, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Pass'],
        ['grade' => 'E', 'points' => null, 'min_score' => 0,  'max_score' => 49,  'remark' => 'Fail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | A-LEVEL (S5-S6) — UACE-style Points
    |--------------------------------------------------------------------------
    | PROPOSED boundaries based on common Ugandan sixth-form practice.
    | These are NOT confirmed — awaiting sign-off before finalizing.
    */
    'a-level' => [
        ['grade' => 'A', 'points' => 6, 'min_score' => 80, 'max_score' => 100, 'remark' => 'Excellent'],
        ['grade' => 'B', 'points' => 5, 'min_score' => 70, 'max_score' => 79,  'remark' => 'Very Good'],
        ['grade' => 'C', 'points' => 4, 'min_score' => 60, 'max_score' => 69,  'remark' => 'Good'],
        ['grade' => 'D', 'points' => 3, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Satisfactory'],
        ['grade' => 'E', 'points' => 2, 'min_score' => 40, 'max_score' => 49,  'remark' => 'Below Average'],
        ['grade' => 'O', 'points' => 1, 'min_score' => 20, 'max_score' => 39,  'remark' => 'Ordinary Pass'],
        ['grade' => 'F', 'points' => 0, 'min_score' => 0,  'max_score' => 19,  'remark' => 'Fail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | NURSERY — Descriptive/Developmental Assessment
    |--------------------------------------------------------------------------
    | No percentage marks, no points. Each developmental domain is rated on a
    | 4-level descriptive scale. The 'grade' here is the rating label; the
    | 'remark' is a brief descriptor shown alongside it on the report card.
    |
    | NOTE: The 4 domain types (Literacy, Numeracy, Motor Skills, Social/Emotional)
    | are NOT stored here — they are the "subjects" of nursery assessment,
    | stored in the nursery_assessments table keyed per-student per-term.
    |
    | These rows are seeded into SchoolGradingSystem for nursery standards
    | (Baby Class, Middle Class, Top Class) so the grading lookup infrastructure
    | is consistent across all level types.
    */
    'nursery' => [
        ['grade' => 'Excellent',        'points' => null, 'min_score' => null, 'max_score' => null, 'remark' => 'Consistently exceeds expectations'],
        ['grade' => 'Good',             'points' => null, 'min_score' => null, 'max_score' => null, 'remark' => 'Meets expectations appropriately'],
        ['grade' => 'Satisfactory',     'points' => null, 'min_score' => null, 'max_score' => null, 'remark' => 'Developing with support'],
        ['grade' => 'Needs Improvement','points' => null, 'min_score' => null, 'max_score' => null, 'remark' => 'Requires significant support'],
    ],

];
