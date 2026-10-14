<?php

namespace App\Support;

class ParentProfileTabs
{
    public const VISIBLE = ['overview', 'children'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'overview' => 'Overview',
            'children' => 'My children',
        ];
    }
}
