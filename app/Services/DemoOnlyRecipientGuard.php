<?php

namespace App\Services;

use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindApproval;
use Illuminate\Support\Facades\Log;

/**
 * Demo-only sending guard (WHATSAPP_DEMO_ONLY_RECIPIENTS=true), for a
 * deployment that holds only demo schools (the live demo on production).
 *
 * Checked in WhatsAppBusinessService::demoBlocked(), the one place every
 * send method passes before any HTTP call. While the flag is on, a message
 * may go ONLY to:
 *
 *   1. a number with a demo binding (whatsapp_users.demo_linked_at set) to
 *      a demo school: BIND, an approved demo BIND request, or Try Demo;
 *   2. a number with a pending demo BIND request at a demo school;
 *   3. the sender of the inbound message being answered right now, unless
 *      that number is linked to a school that is not a demo school;
 *   4. a school admin's own WhatsApp verification code, and only that
 *      message, to the number that admin is verifying right now.
 *
 * Item 4 is a system message. It does not open the guard for any other
 * flow, body, number, or user. Marks releases, fee reminders, parent
 * links, and every other send keep the checks below. With the flag off
 * this class does nothing.
 *
 * Every other recipient is refused and the refusal is logged with the last
 * three digits only. Numbers are compared on their full digits, never on a
 * suffix. Every WhatsApp row for that number is checked: one row linked to a
 * school that is not a demo school refuses the send, even when another row
 * for the same digits is a demo binding.
 */
final class DemoOnlyRecipientGuard
{
    /**
     * Flow type for the one system message this guard lets past the
     * demo-only list: the code a school admin is sending to their own
     * number during setup.
     */
    public const ADMIN_VERIFICATION = 'admin_whatsapp_verification';

    /** @var array<int, string> digits of the senders being answered (nested webhooks) */
    private static array $inboundSenders = [];

    /** @var array<int, string> digits an approval reply is allowed to reach */
    private static array $replyPhones = [];

    /**
     * Nested scopes: each entry is the admin user id and the exact digits
     * they are verifying. Only the matching verification message may leave.
     *
     * @var array<int, array{digits: string, user_id: int}>
     */
    private static array $adminVerifications = [];

    public static function enabled(): bool
    {
        return (bool) config('services.whatsapp.demo_only_recipients', false);
    }

    public static function beginInbound(string $phone): void
    {
        self::$inboundSenders[] = self::digits($phone);
    }

    public static function endInbound(): void
    {
        array_pop(self::$inboundSenders);
    }

    /**
     * Let one approval or rejection reply reach the number that asked.
     * Only the phone passed in, and only while the callback runs.
     */
    public static function duringReply(string $phone, callable $callback): mixed
    {
        self::$replyPhones[] = self::digits($phone);
        try {
            return $callback();
        } finally {
            array_pop(self::$replyPhones);
        }
    }

    /**
     * Let one school admin's verification code reach the number they are
     * verifying. Only that user id and those digits, and only while the
     * callback runs. The callback must send ADMIN_VERIFICATION with the
     * fixed verification body; anything else is still refused.
     */
    public static function duringAdminVerification(int $adminUserId, string $phone, callable $callback): mixed
    {
        self::$adminVerifications[] = [
            'digits' => self::digits($phone),
            'user_id' => $adminUserId,
        ];
        try {
            return $callback();
        } finally {
            array_pop(self::$adminVerifications);
        }
    }

    /** Test seam. */
    public static function flush(): void
    {
        self::$inboundSenders = [];
        self::$replyPhones = [];
        self::$adminVerifications = [];
    }

    /** Null when the send may go out, otherwise the refusal reason. */
    public static function refusal(string $phone, ?string $flowType = null, ?int $userId = null, ?string $body = null): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $digits = self::digits($phone);
        $reason = self::reason($digits, $flowType, $userId, $body);
        if ($reason !== null) {
            Log::warning('[demo-only-guard] WhatsApp send refused', [
                'to' => '•••' . substr($digits, -3),
                'reason' => $reason,
            ]);
        }

        return $reason;
    }

    private static function reason(string $digits, ?string $flowType = null, ?int $userId = null, ?string $body = null): ?string
    {
        if ($digits === '') {
            return 'no_number';
        }

        if (self::adminVerificationAllowed($digits, $flowType, $userId, $body)) {
            return null;
        }

        $demoIds = School::query()->where('is_demo', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rows = WhatsAppUser::query()->whereIn('phone', ['+' . $digits, $digits])->get();

        foreach ($rows as $row) {
            if (self::linkedToRealSchool($row, $demoIds)) {
                return 'linked_to_a_school_that_is_not_a_demo_school';
            }
        }

        foreach ($rows as $row) {
            if ($row->demo_linked_at !== null && in_array((int) $row->school_id, $demoIds, true)) {
                return null;
            }
        }

        $pending = ParentLinkRequest::query()
            ->whereIn('phone', ['+' . $digits, $digits])
            ->where('status', 'pending')
            ->where('flow_token', DemoBindApproval::REQUEST_TOKEN)
            ->whereIn('school_id', $demoIds)
            ->exists();
        if ($pending) {
            return null;
        }

        if (in_array($digits, self::$inboundSenders, true) || in_array($digits, self::$replyPhones, true)) {
            return null;
        }

        return 'not_a_demo_recipient';
    }

    /**
     * True only for the verification code itself, sent to the number the
     * active school admin is verifying. A different flow, body, number, or
     * user falls through to the recipient checks that follow.
     */
    private static function adminVerificationAllowed(string $digits, ?string $flowType, ?int $userId, ?string $body): bool
    {
        if ($flowType !== self::ADMIN_VERIFICATION || $userId === null || $userId < 1 || $body === null) {
            return false;
        }

        if (! preg_match('/\AYour KlassApp verification code is: \d{6}\. It expires in 5 minutes\.\z/', $body)) {
            return false;
        }

        $scoped = false;
        foreach (self::$adminVerifications as $scope) {
            if ($scope['digits'] === $digits && $scope['user_id'] === $userId && $digits !== '') {
                $scoped = true;
                break;
            }
        }
        if (! $scoped) {
            return false;
        }

        $admin = User::query()->whereKey($userId)->first(['id', 'usergroup_id', 'school_id', 'status']);
        if ($admin === null || (int) $admin->usergroup_id !== 3 || $admin->status !== 'active' || ! $admin->school_id) {
            return false;
        }

        return School::query()->whereKey((int) $admin->school_id)->exists();
    }

    /** @param array<int, int> $demoIds */
    private static function linkedToRealSchool(WhatsAppUser $row, array $demoIds): bool
    {
        $schoolIds = array_filter([
            $row->school_id ? (int) $row->school_id : null,
            $row->user_id ? (int) User::query()->whereKey($row->user_id)->value('school_id') : null,
        ]);
        foreach ($schoolIds as $id) {
            if (! in_array($id, $demoIds, true)) {
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
