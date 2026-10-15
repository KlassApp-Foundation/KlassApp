<?php

namespace App\Support;

class ParentProfileTabs
{
    public const VISIBLE = ['overview', 'children'];

    /**
     * Staff viewing a parent read "Children"; "My children" is only for a parent
     * looking at their own profile.
     *
     * @return array<string, string>
     */
    public static function labels(bool $ownProfile = false): array
    {
        return [
            'overview' => 'Overview',
            'children' => $ownProfile ? 'My children' : 'Children',
        ];
    }
}
