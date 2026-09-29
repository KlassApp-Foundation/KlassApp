<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;

/**
 * Hard guard: demo schools never send real outbound messages.
 *
 * Every is_demo school (including the ones already seeded on production) is
 * blocked across all four channels:
 *   - email  (via the MessageSending listener DemoSchoolBlockOutboundMail)
 *   - SMS    (via MSG91::sendSMS)
 *   - WhatsApp (via WhatsAppBusinessService entry points)
 *   - push   (via SendPushNotification::sendNotification / sendTeacherNotification)
 *
 * Resolutions are per-request cached; when no demo school exists every check
 * is a single cached lookup away from a cheap false.
 */
final class DemoSchoolCommsGuard
{
    /** @var array<int>|null */
    private static ?array $demoSchoolIds = null;

    /** @return array<int> */
    public static function demoSchoolIds(): array
    {
        if (self::$demoSchoolIds === null) {
            self::$demoSchoolIds = School::where('is_demo', true)->pluck('id')->all();
        }

        return self::$demoSchoolIds;
    }

    /** Test seam. */
    public static function flushCache(): void
    {
        self::$demoSchoolIds = null;
    }

    public static function blocksSchool(?int $schoolId): bool
    {
        return $schoolId !== null && in_array($schoolId, self::demoSchoolIds(), true);
    }

    public static function blocksUser(?int $userId): bool
    {
        if ($userId === null || ! self::demoSchoolIds()) {
            return false;
        }

        return self::blocksSchool((int) User::whereKey($userId)->value('school_id'));
    }

    public static function blocksEmail(?string $email): bool
    {
        if (! $email || ! self::demoSchoolIds()) {
            return false;
        }

        $email = strtolower(trim($email));

        // All demo-school accounts use this reserved domain; check it first so
        // queued mail for removed demo users still cannot escape.
        if (str_ends_with($email, '@demo.klassapp.test')) {
            return true;
        }

        return self::blocksSchool((int) User::where('email', $email)->value('school_id'));
    }

    public static function blocksPhone(?string $phone): bool
    {
        if (! $phone || ! self::demoSchoolIds()) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return false;
        }

        $user = User::where('mobile_no', $phone)
            ->orWhere('mobile_no', 'like', '%' . substr($digits, -9))
            ->first(['id', 'school_id']);

        if ($user && self::blocksSchool((int) $user->school_id)) {
            return true;
        }

        $wa = WhatsAppUser::where('phone', $phone)
            ->orWhere('phone', 'like', '%' . substr($digits, -9))
            ->first(['school_id', 'user_id']);

        if ($wa) {
            return self::blocksSchool((int) $wa->school_id) || self::blocksUser($wa->user_id ? (int) $wa->user_id : null);
        }

        return false;
    }

    public static function blocksWhatsApp(?string $phone, ?int $userId = null): bool
    {
        if ($userId !== null && self::blocksUser($userId)) {
            return true;
        }

        return self::blocksPhone($phone);
    }

    public static function blocksDeviceToken(?string $token): bool
    {
        if (! $token || ! self::demoSchoolIds()) {
            return false;
        }

        return self::blocksSchool((int) User::where('device_id', $token)->value('school_id'));
    }
}
