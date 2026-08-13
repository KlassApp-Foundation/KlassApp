<?php

/**
 * Head-teacher report-card comments — derived at PDF-render time.
 *
 * Same score-band structure as the class-teacher bank
 * (config/report_card_comments.php) so the identical band lookup applies.
 * Comment phrases are pending the operator-confirmed 6-tier table and will
 * be filled into each band's 'comments' array; empty arrays render a blank
 * Head Teacher box (safe fallback, never a wrong phrase).
 */

return [
    'lower' => [
        ['min' => 520, 'max' => 600, 'comments' => []],
        ['min' => 440, 'max' => 519, 'comments' => []],
        ['min' => 360, 'max' => 439, 'comments' => []],
        ['min' => 280, 'max' => 359, 'comments' => []],
        ['min' => 200, 'max' => 279, 'comments' => []],
        ['min' => 0, 'max' => 199, 'comments' => []],
    ],

    'upper' => [
        ['min' => 360, 'max' => 400, 'comments' => []],
        ['min' => 320, 'max' => 359, 'comments' => []],
        ['min' => 280, 'max' => 319, 'comments' => []],
        ['min' => 240, 'max' => 279, 'comments' => []],
        ['min' => 200, 'max' => 239, 'comments' => []],
        ['min' => 100, 'max' => 199, 'comments' => []],
    ],
];
