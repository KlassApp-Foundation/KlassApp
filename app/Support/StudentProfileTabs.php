<?php

namespace App\Support;

/**
 * Tabs on the admin student profile (design: Overview, Performance, Attendance, Fees, Parents).
 *
 * Health and support, Documents, and Notes are registered here so they
 * can be added later. They are not rendered: no empty tab and no
 * disabled placeholder.
 */
class StudentProfileTabs
{
    public const VISIBLE = ['overview', 'performance', 'attendance', 'fees', 'parents'];

    public const DEFERRED = ['health', 'documents', 'notes'];

    /** Old tab names still open the right tab. */
    private const ALIASES = ['academics' => 'performance'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'overview' => 'Overview',
            'performance' => 'Performance',
            'attendance' => 'Attendance',
            'fees' => 'Fees',
            'parents' => 'Parents',
        ];
    }

    public static function normalize(string $tab): string
    {
        $tab = self::ALIASES[$tab] ?? $tab;

        return in_array($tab, self::VISIBLE, true) ? $tab : 'overview';
    }
}
