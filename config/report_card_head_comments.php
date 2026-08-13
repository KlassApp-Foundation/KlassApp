<?php

/**
 * Head-teacher report-card comments — derived at PDF-render time.
 *
 * Same score-band structure as the class-teacher bank
 * (config/report_card_comments.php) so the identical band lookup applies.
 * Phrases confirmed by the school operator; selected deterministically and
 * never rendered identical to the class-teacher comment (see
 * ReportCardCommentService::headTeacherCommentFor collision guard).
 */

return [
    'lower' => [
        ['min' => 520, 'max' => 600, 'comments' => [
            "This has been an outstanding term overall. Keep up this standard.",
            "This is truly commendable work from start to finish.",
            "A result the whole school can be proud of.",
            "Exceptional effort has clearly paid off this term.",
        ]],
        ['min' => 440, 'max' => 519, 'comments' => [
            "A strong, consistent term of work.",
            "Well done. This level of commitment truly stands out.",
            "Solid results that reflect real dedication.",
            "Keep building on this strong foundation.",
        ]],
        ['min' => 360, 'max' => 439, 'comments' => [
            "A pleasing term with clear signs of progress.",
            "Steady improvement is evident. Well done.",
            "This is a good foundation to build further on.",
            "Progress this term has been encouraging.",
        ]],
        ['min' => 280, 'max' => 359, 'comments' => [
            "There are clear signs of growth to build on.",
            "With continued effort, real progress is within reach.",
            "This term shows promise. Keep pushing forward.",
        ]],
        ['min' => 200, 'max' => 279, 'comments' => [
            "More consistent effort will bring better results.",
            "There is real potential waiting to be unlocked.",
            "A stronger push next term will make a big difference.",
        ]],
        ['min' => 0, 'max' => 199, 'comments' => [
            "Extra support and effort are needed going forward.",
            "Let us work together to turn this around next term.",
            "This term calls for renewed focus and commitment.",
        ]],
    ],

    'upper' => [
        ['min' => 360, 'max' => 400, 'comments' => [
            "This has been an outstanding term overall. Keep up this standard.",
            "This is truly commendable work from start to finish.",
            "A result the whole school can be proud of.",
            "Exceptional effort has clearly paid off this term.",
        ]],
        ['min' => 320, 'max' => 359, 'comments' => [
            "A strong, consistent term of work.",
            "Well done. This level of commitment truly stands out.",
            "Solid results that reflect real dedication.",
            "Keep building on this strong foundation.",
        ]],
        ['min' => 280, 'max' => 319, 'comments' => [
            "A pleasing term with clear signs of progress.",
            "Steady improvement is evident. Well done.",
            "This is a good foundation to build further on.",
            "Progress this term has been encouraging.",
        ]],
        ['min' => 240, 'max' => 279, 'comments' => [
            "There are clear signs of growth to build on.",
            "With continued effort, real progress is within reach.",
            "This term shows promise. Keep pushing forward.",
        ]],
        ['min' => 200, 'max' => 239, 'comments' => [
            "More consistent effort will bring better results.",
            "There is real potential waiting to be unlocked.",
            "A stronger push next term will make a big difference.",
        ]],
        ['min' => 100, 'max' => 199, 'comments' => [
            "Extra support and effort are needed going forward.",
            "Let us work together to turn this around next term.",
            "This term calls for renewed focus and commitment.",
        ]],
    ],
];
