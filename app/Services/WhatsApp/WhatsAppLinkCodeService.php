<?php

namespace App\Services\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\User;
use App\Models\WhatsAppLinkCode;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * "Open WhatsApp and send this code" linking.
 *
 * The page shows a six-character code, a wa.me link with the code prefilled and
 * a QR for the same link. When the message arrives from a number, that number
 * is linked to the code's owner. Nothing is typed, so no country format is
 * required; any international number works.
 *
 * Codes: uppercase letters/digits without 0/O/1/I/L, HMAC-SHA256 at rest,
 * ten-minute expiry, single use, and never a code with fewer than three
 * different characters.
 */
class WhatsAppLinkCodeService
{
    public const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const MESSAGE_PREFIX = 'Link KlassApp: ';

    public function __construct(private WhatsAppBusinessService $whatsApp) {}

    /**
     * @return array{ok: bool, message: string, code: ?string, url: ?string, business_number: string, expires_at: ?string}
     */
    public function issue(User $user, ?string $ip = null): array
    {
        $number = $this->businessNumber();

        if (! $user->school_id || $user->status !== 'active') {
            return ['ok' => false, 'message' => 'Sign in with an active account to link WhatsApp.', 'code' => null, 'url' => null, 'business_number' => $number, 'expires_at' => null];
        }

        if (! $this->tableReady()) {
            return ['ok' => false, 'message' => 'WhatsApp linking is not available yet.', 'code' => null, 'url' => null, 'business_number' => $number, 'expires_at' => null];
        }

        if ($this->limitsEnforced()) {
            $userKey = 'wa-link:user:'.$user->id;
            $ipKey = trim((string) $ip) !== '' ? 'wa-link:ip:'.hash('sha256', (string) $ip) : null;

            if (RateLimiter::tooManyAttempts($userKey, (int) config('services.whatsapp.link_codes_per_user_hour', 10))) {
                return ['ok' => false, 'message' => 'Too many codes. Wait a few minutes and try again.', 'code' => null, 'url' => null, 'business_number' => $number, 'expires_at' => null];
            }
            if ($ipKey !== null && RateLimiter::tooManyAttempts($ipKey, (int) config('services.whatsapp.link_codes_per_ip_hour', 30))) {
                return ['ok' => false, 'message' => 'Too many codes from this network. Wait a few minutes and try again.', 'code' => null, 'url' => null, 'business_number' => $number, 'expires_at' => null];
            }

            RateLimiter::hit($userKey, 3600);
            if ($ipKey !== null) {
                RateLimiter::hit($ipKey, 3600);
            }
        }

        $code = $this->generateCode();
        $minutes = max(1, (int) config('services.whatsapp.link_code_ttl_minutes', 10));

        // One active code per person: a new page load replaces the old one.
        WhatsAppLinkCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $row = WhatsAppLinkCode::query()->create([
            'school_id' => (int) $user->school_id,
            'user_id' => (int) $user->id,
            'code_hash' => $this->hashCode($code),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        Log::info('WhatsApp link code issued', [
            'user_id' => (int) $user->id,
            'school_id' => (int) $user->school_id,
        ]);

        return [
            'ok' => true,
            'message' => 'Send this code from your WhatsApp.',
            'code' => $code,
            'url' => $this->whatsAppLink($code),
            'business_number' => $number,
            'expires_at' => $row->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Handle an inbound message. True when it was a link attempt we answered.
     */
    public function handleInbound(string $from, string $body): bool
    {
        if (! $this->tableReady()) {
            return false;
        }

        $digits = $this->internationalDigits($from);
        if ($digits === null) {
            return false;
        }

        $outcome = DB::transaction(function () use ($body, $digits) {
            $candidates = $this->candidateCodes($body);
            if ($candidates === []) {
                return null;
            }

            $hashes = array_map(fn (string $code) => $this->hashCode($code), $candidates);
            $row = WhatsAppLinkCode::query()
                ->whereIn('code_hash', $hashes)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                return null;
            }

            $row->forceFill(['used_at' => now()])->save();

            $user = User::query()
                ->whereKey($row->user_id)
                ->where('school_id', $row->school_id)
                ->where('status', 'active')
                ->first();
            if (! $user) {
                return ['phone' => '+'.$digits, 'reply' => 'This link is no longer valid. Ask an admin for a new code.', 'outcome' => 'no_user'];
            }

            $phone = '+'.$digits;
            $taken = WhatsAppUser::query()
                ->where('phone', $phone)
                ->whereNotNull('user_id')
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($taken) {
                return ['phone' => $phone, 'reply' => 'This number is already linked to another KlassApp account. Use a different number.', 'outcome' => 'taken'];
            }

            $rowForUser = WhatsAppUser::query()->where('user_id', $user->id)->first();
            $existingPhoneRow = WhatsAppUser::query()->where('phone', $phone)->first();

            if ($rowForUser !== null && $existingPhoneRow !== null && (int) $rowForUser->id !== (int) $existingPhoneRow->id) {
                $rowForUser->delete();
                $rowForUser = null;
            }

            $target = $rowForUser ?? $existingPhoneRow ?? new WhatsAppUser;
            $target->fill([
                'phone' => $phone,
                'user_id' => $user->id,
                'school_id' => (int) $row->school_id,
                'opted_in' => true,
                'verified_at' => now(),
            ])->save();

            activity()
                ->performedOn($target)
                ->causedBy($user)
                ->withProperties(['phone' => $phone])
                ->useLog('linkwhatsapp')
                ->log('Linked WhatsApp Number');

            return ['phone' => $phone, 'reply' => 'KlassApp has linked this WhatsApp number. You can close this chat.', 'outcome' => 'linked', 'user_id' => (int) $user->id];
        });

        if ($outcome === null) {
            return false;
        }

        Log::info('WhatsApp link attempt', [
            'phone' => WhatsAppPhoneHelper::mask((string) $outcome['phone']),
            'outcome' => (string) $outcome['outcome'],
        ]);

        $this->reply((string) $outcome['phone'], (string) $outcome['reply'], (int) ($outcome['user_id'] ?? 0));

        return true;
    }

    /**
     * @return array{linked: bool, phone: ?string, verified: bool}
     */
    public function status(User $user): array
    {
        $row = WhatsAppUser::query()->where('user_id', $user->id)->first();

        return [
            'linked' => $row !== null && $row->phone !== null && $row->phone !== '',
            'phone' => $row?->phone,
            'verified' => $row?->verified_at !== null,
        ];
    }

    public function generateCode(): string
    {
        $alphabet = self::ALPHABET;
        $last = strlen($alphabet) - 1;

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, $last)];
            }
        } while (count(array_unique(str_split($code))) < 3);

        return $code;
    }

    public function whatsAppLink(string $code): string
    {
        return 'https://wa.me/'.$this->businessDigits().'?text='.rawurlencode(self::MESSAGE_PREFIX.$code);
    }

    public function businessNumber(): string
    {
        $digits = $this->businessDigits();

        return '+'.substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 3).' '.substr($digits, 9);
    }

    private function businessDigits(): string
    {
        $configured = preg_replace('/\D/', '', (string) config('services.whatsapp.business_number')) ?? '';

        return $configured !== '' ? $configured : '256793844906';
    }

    /**
     * @return list<string>
     */
    private function candidateCodes(string $body): array
    {
        if (preg_match_all('/['.preg_quote(self::ALPHABET, '/').']{6}/i', $body, $matches) < 1) {
            return [];
        }

        return array_values(array_unique(array_map('strtoupper', array_slice($matches[0], 0, 8))));
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', strtoupper($code), (string) config('app.key'));
    }

    private function internationalDigits(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '' || str_starts_with($digits, '0') || strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    private function tableReady(): bool
    {
        return Schema::hasTable('whatsapp_link_codes');
    }

    private function limitsEnforced(): bool
    {
        if (! app()->environment('testing')) {
            return true;
        }

        return (bool) config('services.whatsapp.enforce_link_limits_in_tests', false);
    }

    private function reply(string $phone, string $text, int $userId): void
    {
        if (! $this->whatsApp->isConfigured() || $userId < 1) {
            return;
        }

        try {
            $this->whatsApp->sendText($phone, $text, 'service', $userId);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp link reply failed', ['phone' => WhatsAppPhoneHelper::mask($phone)]);
        }
    }
}
