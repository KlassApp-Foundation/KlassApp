<?php

namespace App\Support;

/**
 * Tabs on the admin teacher profile.
 *
 * Timetable, leave, and activity stay registered so they can be added
 * later. They are not rendered: no empty tab and no disabled placeholder.
 */
class TeacherProfileTabs
{
    public const VISIBLE = ['overview', 'teaches'];

    public const DEFERRED = ['timetable', 'leave', 'activity'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'overview' => 'Overview',
            'teaches' => 'Teaches',
        ];
    }
}
