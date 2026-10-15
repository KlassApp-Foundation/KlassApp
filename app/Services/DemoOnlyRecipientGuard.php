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
 *      that number is linked to a school that is not a demo school.
 *
 * Every other recipient is refused and the refusal is logged with the last
 * three digits only. Numbers are compared on their full digits, never on a
 * suffix. Every WhatsApp row for that number is checked: one row linked to a
 * school that is not a demo school refuses the send, even when another row
 * for the same digits is a demo binding. With the flag off this class does nothing.
 */
final class DemoOnlyRecipientGuard
{
    /** @var array<int, string> digits of the senders being answered (nested webhooks) */
    private static array $inboundSenders = [];

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

    /** Test seam. */
    public static function flush(): void
    {
        self::$inboundSenders = [];
    }

    /** Null when the send may go out, otherwise the refusal reason. */
    public static function refusal(string $phone): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $digits = self::digits($phone);
        $reason = self::reason($digits);
        if ($reason !== null) {
            Log::warning('[demo-only-guard] WhatsApp send refused', [
                'to' => '•••' . substr($digits, -3),
                'reason' => $reason,
            ]);
        }

        return $reason;
    }

    private static function reason(string $digits): ?string
    {
        if ($digits === '') {
            return 'no_number';
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

        if (in_array($digits, self::$inboundSenders, true)) {
            return null;
        }

        return 'not_a_demo_recipient';
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
