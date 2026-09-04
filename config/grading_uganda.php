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
    | PRIMARY (P1-P7) — P.7 Nine-Band Percentage Scale
    |--------------------------------------------------------------------------
    | Uganda P.7-style termly grading bands (confirmed with the school operator,
    | Kabale reference school 104). This is the ONE primary scale seeded as the
    | onboarding smart default (schema decision: one default scale per
    | standard). Per-section P.4-P6 / P.1-P3 / Nursery band variants are logged
    | in knowledge.md as a future per-section grading enhancement — not seeded.
    */
    'primary' => [
        ['grade' => '1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
        ['grade' => '2', 'points' => 2, 'min_score' => 85, 'max_score' => 94,  'remark' => 'V.Good'],
        ['grade' => '3', 'points' => 3, 'min_score' => 75, 'max_score' => 84,  'remark' => 'Good'],
        ['grade' => '4', 'points' => 4, 'min_score' => 65, 'max_score' => 74,  'remark' => 'F.Good/Q.Good'],
        ['grade' => '5', 'points' => 5, 'min_score' => 60, 'max_score' => 64,  'remark' => 'Promising'],
        ['grade' => '6', 'points' => 6, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Fair'],
        ['grade' => '7', 'points' => 7, 'min_score' => 45, 'max_score' => 49,  'remark' => 'Work hard'],
        ['grade' => '8', 'points' => 8, 'min_score' => 40, 'max_score' => 44,  'remark' => 'Aim higher'],
        ['grade' => '9', 'points' => 9, 'min_score' => 0,  'max_score' => 39,  'remark' => 'More effort'],
    ],

    /*
    |--------------------------------------------------------------------------
    | O-LEVEL (S1-S4) — A-E Letter Grades (NLSC/UCE system)
    |--------------------------------------------------------------------------
    | Uganda's New Lower Secondary Curriculum grading used for all
    | O-Level termly assessments. Bands match the UNEB UCE release statement
    | figures confirmed via the Monitor's UCE grading article and the UCE 2024
    | release statement (https://ereg.uneb.ac.ug/files/oNVRW_uce_2024_release_statement.pdf):
    | A = 80-100 (Exceptional), B = 70-79, C = 60-69, D = 50-59, E = 0-49.
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
    | UACE principal-subject points as cited from NymyNet's UACE grading guide
    | (https://nymynet.com/uace-grading-system-2025-how-to-count-a-level-points-in-uganda/):
    | A=6, B=5, C=4, D=3, E=2, O=1, F=0. Boundaries follow the same guide's
    | published percentage ranges.
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
