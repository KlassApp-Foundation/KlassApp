<?php

namespace Tests\Support\Onboarding;

/**
 * Approved school-type recommendations. Uganda uses these UNEB classes.
 * A-level streams are the usual combinations. Other levels stay unstreamed.
 */
final class SchoolTypePresetFixtures
{
    /** @var list<string> */
    public const A_LEVEL_STREAMS = ['PCM', 'BCM', 'HEG', 'MEG'];

    /** @var list<string> */
    public const CATEGORIES = [
        'nursery',
        'primary',
        'primary_nursery',
        'o_level',
        'o_a_level',
    ];
}
