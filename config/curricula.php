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
         * The old (pre-NLSC) O-level scale is the SCHOOL'S OWN table.
         *
         * UNEB never published its legacy UCE cut-offs as one fixed national
         * table, so the school owns the boundaries and edits them on the
         * exam-type settings page. The D1-F9 letters and 1-9 points are fixed;
         * the table in config/grading_uganda.php under 'o-level-legacy' is a
         * labelled EXAMPLE shown until the school saves its own, not a
         * national standard. The A-level (UACE) scale did not change between
         * curricula, so it reuses the current bands.
         */
        'legacy' => [
            'label' => 'Old curricula (pre-NLSC) — this school\'s own O-level bands (example shown)',
            'levels' => [
                'o-level' => 'o-level-legacy',
                'a-level' => 'a-level',
            ],
        ],

    ],

];
