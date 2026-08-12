<?php

/**
 * Class-teacher report-card comments — derived at PDF-render time.
 *
 * Comments are selected from a band by deterministic hash (student_id + exam_id)
 * so re-generating the same PDF always yields the identical comment.
 *
 * Source: kabale_class_teacher_comments.xlsx (confirmed bands per school operator).
 * P.4-P.7 lowest band is 100-199 (not 150-199).
 * Nursery uses the same bands as P.1-P.3 — verify nursery total-mark scale is
 * comparable before trusting in production.
 */

return [
    'lower' => [
        ['min' => 520, 'max' => 600, 'comments' => [
            "This performance is excellent.",
            "It's excellent performance.",
            "The performance is excellent.",
            "I am pleased with this performance.",
            "The sky is the limit.",
        ]],
        ['min' => 440, 'max' => 519, 'comments' => [
            "Your child has a sound mind.",
            "This performance is very good.",
            "Thank you for the hard work.",
            "I am impressed with your child's performance.",
        ]],
        ['min' => 360, 'max' => 439, 'comments' => [
            "This performance is promising.",
            "I am pleased with the improvement shown.",
            "Your child has exhibited good performance.",
            "Your child has registered good performance.",
        ]],
        ['min' => 280, 'max' => 359, 'comments' => [
            "Your child's performance is promising.",
            "Your child can do better than this.",
            "This performance is encouraging.",
        ]],
        ['min' => 200, 'max' => 279, 'comments' => [
            "Encouraged to work hard.",
            "You still have hopes in you.",
            "You have the capacity to do better.",
            "You are capable of doing better.",
        ]],
        ['min' => 0, 'max' => 199, 'comments' => [
            "There is still room for improvement.",
            "We shall work together to ensure good performance.",
            "Constant revision will help you improve this performance.",
        ]],
    ],

    'upper' => [
        ['min' => 360, 'max' => 400, 'comments' => [
            "This performance is excellent.",
            "It's excellent performance.",
            "The performance is excellent.",
            "I am pleased with this performance.",
            "The sky is the limit.",
        ]],
        ['min' => 320, 'max' => 359, 'comments' => [
            "Your child has a sound mind.",
            "This performance is very good.",
            "Thank you for the hard work.",
            "I am impressed with your child's performance.",
        ]],
        ['min' => 280, 'max' => 319, 'comments' => [
            "This performance is promising.",
            "I am pleased with the improvement shown.",
            "Your child has exhibited good performance.",
            "Your child has registered good performance.",
        ]],
        ['min' => 240, 'max' => 279, 'comments' => [
            "Your child's performance is promising.",
            "Your child can do better than this.",
            "This performance is encouraging.",
        ]],
        ['min' => 200, 'max' => 239, 'comments' => [
            "Encouraged to work hard.",
            "You still have hopes in you.",
            "You have the capacity to do better.",
            "You are capable of doing better.",
        ]],
        ['min' => 100, 'max' => 199, 'comments' => [
            "There is still room for improvement.",
            "We shall work together to ensure good performance.",
            "Constant revision will help you improve this performance.",
        ]],
    ],
];