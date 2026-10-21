<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;

/**
 * Hard guard: schools flagged is_test never receive outbound WhatsApp.
 *
 * Checked from WhatsAppBusinessService::demoBlocked(), before the demo-only
 * recipient switch. This class does not read services.whatsapp.demo_only_recipients.
 * Turning that switch off does not open sending for an is_test school.
 *
 * A recipient belongs to a test school when any of these is an is_test school:
 * the user id's school, a whatsapp_users row's school, that row's user, or a
 * users.mobile_no that is the same full number. Numbers are compared on their
 * full digits (and the trunk-0 form of that same number). A shorter suffix is
 * never used, so a real number cannot be blocked because it shares digits.
 *
 * Two sends still go out, and only those: the scoped admin verification code
 * to the number that admin is verifying, and a reply to the exact inbound
 * sender being answered. Anything inside DemoSchoolCommsGuard::duringBusinessSend()
 * stays blocked, including those two.
 */
final class TestSchoolCommsGuard
{
    /** @var array<int, int>|null */
    private static ?array $testSchoolIds = null;

    /** Test seam. Clears the per-request school-id cache. */
    public static function flushCache(): void
    {
        self::$testSchoolIds = null;
    }

    /**
     * The blocking test school id, or null when the send may go out.
     */
    public static function blocksWhatsApp(?string $phone, ?int $userId = null, ?string $flowType = null, ?string $body = null): ?int
    {
        $schoolId = self::matchingTestSchoolId($phone, $userId);
        if ($schoolId === null) {
            return null;
        }

        if (DemoSchoolCommsGuard::businessSendInProgress()) {
            return $schoolId;
        }

        $digits = DemoOnlyRecipientGuard::digitsOnly((string) $phone);
        if (DemoOnlyRecipientGuard::adminVerificationAllowed($digits, $flowType, $userId, $body)) {
            return null;
        }

        if (DemoOnlyRecipientGuard::isCurrentInboundSender((string) $phone)) {
            return null;
        }

        return $schoolId;
    }

    private static function matchingTestSchoolId(?string $phone, ?int $userId): ?int
    {
        if (self::testSchoolIds() === []) {
            return null;
        }

        if ($userId !== null && $userId > 0) {
            $schoolId = (int) User::query()->whereKey($userId)->value('school_id');
            if (self::isTestSchool($schoolId)) {
                return $schoolId;
            }
        }

        $forms = self::exactForms((string) $phone);
        if ($forms === []) {
            return null;
        }

        $rows = WhatsAppUser::query()
            ->whereIn('phone', $forms)
            ->get(['school_id', 'user_id', 'phone']);

        $userIds = $rows->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->all();
        $schoolByUser = $userIds === []
            ? []
            : User::query()->whereIn('id', $userIds)->pluck('school_id', 'id')->all();

        foreach ($rows as $row) {
            if (! self::matchesFullNumber((string) $row->phone, $forms)) {
                continue;
            }

            $rowSchoolId = (int) $row->school_id;
            if (self::isTestSchool($rowSchoolId)) {
                return $rowSchoolId;
            }

            $viaUser = (int) ($schoolByUser[$row->user_id] ?? 0);
            if (self::isTestSchool($viaUser)) {
                return $viaUser;
            }
        }

        $users = User::query()
            ->whereIn('mobile_no', $forms)
            ->get(['school_id', 'mobile_no']);

        foreach ($users as $user) {
            if (! self::matchesFullNumber((string) $user->mobile_no, $forms)) {
                continue;
            }

            $schoolId = (int) $user->school_id;
            if (self::isTestSchool($schoolId)) {
                return $schoolId;
            }
        }

        return null;
    }

    /** @return array<int, int> */
    private static function testSchoolIds(): array
    {
        if (self::$testSchoolIds === null) {
            self::$testSchoolIds = School::query()
                ->where('is_test', 1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return self::$testSchoolIds;
    }

    private static function isTestSchool(int $schoolId): bool
    {
        return $schoolId > 0 && in_array($schoolId, self::testSchoolIds(), true);
    }

    /**
     * Exact stored spellings of this full number. A leading 0 and a 256
     * prefix are the same complete number. A shorter suffix is not included.
     *
     * @return array<int, string>
     */
    private static function exactForms(string $phone): array
    {
        $digits = self::digits($phone);
        if ($digits === '') {
            return [];
        }

        $forms = array_filter([$phone, $digits, '+'.$digits], fn ($form) => $form !== '');

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $international = '256'.substr($digits, 1);
            $forms[] = $international;
            $forms[] = '+'.$international;
        }

        if (str_starts_with($digits, '256') && strlen($digits) >= 12) {
            $forms[] = '0'.substr($digits, 3);
        }

        return array_values(array_unique($forms));
    }

    /** @param array<int, string> $forms */
    private static function matchesFullNumber(string $stored, array $forms): bool
    {
        if (in_array($stored, $forms, true)) {
            return true;
        }

        $storedDigits = self::digits($stored);
        if ($storedDigits === '') {
            return false;
        }

        foreach ($forms as $form) {
            if ($storedDigits === self::digits($form)) {
                return true;
            }
        }

        return false;
    }

    private static function digits(string $phone): string
    {
        return (string) preg_replace('/\D+/', '', $phone);
    }
}
