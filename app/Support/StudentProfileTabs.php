<?php

namespace App\Support;

/**
 * Tabs on the admin student profile.
 *
 * Health and support, Documents, and Notes are registered here so they
 * can be added later. They are not rendered: no empty tab and no
 * disabled placeholder.
 */
class StudentProfileTabs
{
    public const VISIBLE = ['overview', 'academics', 'attendance', 'fees', 'parents'];

    public const DEFERRED = ['health', 'documents', 'notes'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'overview' => 'Overview',
            'academics' => 'Academics',
            'attendance' => 'Attendance',
            'fees' => 'Fees',
            'parents' => 'Parents and guardians',
        ];
    }
}
