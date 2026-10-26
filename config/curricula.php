<?php

/**
 * Curriculum profiles — which grading scale each school's reports use, per
 * level. A profile maps level types to band-table keys in
 * config/grading_uganda.php (one source of bands; no duplication).
 *
 * The active profile is per school: schools.curriculum_profile stores the
 * profile key; null falls back to 'default_profile' below. Seeding applies
 * the profile's bands into school_grading_systems (GradingHelper).
 */

return [

    'default_profile' => 'new',

    'profiles' => [

        'new' => [
            'label' => 'New competency-based curricula (NLSC/UCE and UACE)',
            'levels' => [
                'o-level' => 'o-level',
                'a-level' => 'a-level',
            ],
        ],

        /*
         * FLAGGED(product-decision): the old (pre-NLSC) O-level scale below
         * follows the widely published legacy UCE letter bands (D1 best ...
         * F9 fail), but the exact percentage boundaries still need the
         * product owner's confirmation. Listed in the Phase 5 PR as a
         * decision item; the A-level (UACE) scale did not change between
         * curricula, so it reuses the current bands.
         */
        'legacy' => [
            'label' => 'Old curricula (pre-NLSC) — bands need confirmation',
            'levels' => [
                'o-level' => 'o-level-legacy',
                'a-level' => 'a-level',
            ],
        ],

    ],

];
