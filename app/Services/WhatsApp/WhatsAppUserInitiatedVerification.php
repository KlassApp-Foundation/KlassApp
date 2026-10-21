<?php

namespace App\Services\WhatsApp;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Models\WhatsAppVerificationChallenge;
use App\Services\WhatsAppBusinessService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * User-initiated WhatsApp verification for school-admin setup.
 *
 * The wizard (and later Toshi) shows a short code and a wa.me link. The admin
 * sends that code from their own WhatsApp. The inbound webhook calls
 * handleInbound(). A match on the full number sets whatsapp_users.verified_at.
 *
 * The plain code is returned once to the signed-in admin and stored only as
 * an HMAC. It is not written to the log.
 */
class WhatsAppUserInitiatedVerification
{
    /**
     * Uppercase letters and digits without 0/O, 1/I/L. Six of these are what
     * the admin reads out and sends.
     */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const MESSAGE_PREFIX = 'Verify KlassApp: ';

    public const EXPIRED_MESSAGE = 'This code expired. Get a new code.';

    public function __construct(private WhatsAppBusinessService $whatsApp) {}

    /**
     * @return array{ok: bool, message: string, phone: string, code: ?string, url: ?string}
     */
    public function issue(int $adminUserId, string $phone, ?string $ip = null): array
    {
        $empty = [
            'ok' => false,
            'message' => 'Sign in before verifying WhatsApp.',
            'phone' => trim($phone),
            'code' => null,
            'url' => null,
        ];

        $admin = User::query()->whereKey($adminUserId)->first();
        if ($admin === null || $admin->status !== 'active' || (int) $admin->usergroup_id !== 3 || ! $admin->school_id) {
            return $empty;
        }

        $canonical = $this->canonicalPhone($phone);
        if ($canonical === null) {
            return [
                'ok' => false,
                'message' => 'Enter a full WhatsApp number, including the country code.',
                'phone' => trim($phone),
                'code' => null,
                'url' => null,
            ];
        }

        $taken = WhatsAppUser::query()
            ->where('phone', $canonical)
            ->where(function ($query) use ($adminUserId) {
                $query->whereNull('user_id')->orWhere('user_id', '!=', $adminUserId);
            })
            ->exists();
        if ($taken) {
            return [
                'ok' => false,
                'message' => "WhatsApp number {$canonical} is already registered to another account. Use a different number.",
                'phone' => $canonical,
                'code' => null,
                'url' => null,
            ];
        }

        if (! $this->tableReady()) {
            return [
                'ok' => false,
                'message' => 'WhatsApp verification is not available yet. Continue setup and try again later.',
                'phone' => $canonical,
                'code' => null,
                'url' => null,
            ];
        }

        if ($this->limitsEnforced()) {
            $phoneKey = $this->phoneRateKey($canonical);
            $ipKey = $this->ipRateKey($ip);
            $phoneLimit = $this->intConfig('codes_per_phone', 5);
            $ipLimit = $this->intConfig('codes_per_ip', 10);
            $phoneWindow = $this->intConfig('phone_window_seconds', 3600);
            $ipWindow = $this->intConfig('ip_window_seconds', 3600);

            if (RateLimiter::tooManyAttempts($phoneKey, $phoneLimit)) {
                return [
                    'ok' => false,
                    'message' => 'Too many codes for this number. Wait and try again.',
                    'phone' => $canonical,
                    'code' => null,
                    'url' => null,
                ];
            }

            if ($ipKey !== null && RateLimiter::tooManyAttempts($ipKey, $ipLimit)) {
                return [
                    'ok' => false,
                    'message' => 'Too many codes from this network. Wait and try again.',
                    'phone' => $canonical,
                    'code' => null,
                    'url' => null,
                ];
            }

            RateLimiter::hit($phoneKey, $phoneWindow);
            if ($ipKey !== null) {
                RateLimiter::hit($ipKey, $ipWindow);
            }
        }

        $code = $this->generateCode();
        $digits = ltrim($canonical, '+');

        WhatsAppVerificationChallenge::query()
            ->where('user_id', $admin->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        WhatsAppVerificationChallenge::query()
            ->where('phone_digits', $digits)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        WhatsAppVerificationChallenge::query()->create([
            'school_id' => (int) $admin->school_id,
            'user_id' => (int) $admin->id,
            'phone' => $canonical,
            'phone_digits' => $digits,
            'code_hash' => $this->hashCode($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->intConfig('ttl_minutes', 10)),
            'used_at' => null,
        ]);

        Log::info('WhatsApp verification code issued', [
            'phone' => WhatsAppPhoneHelper::mask($canonical),
            'user_id' => (int) $admin->id,
            'school_id' => (int) $admin->school_id,
        ]);

        return [
            'ok' => true,
            'message' => 'Send this code from your WhatsApp. This page updates when it arrives.',
            'phone' => $canonical,
            'code' => $code,
            'url' => $this->whatsAppLink($code),
        ];
    }

    public function generateCode(): string
    {
        $alphabet = self::ALPHABET;
        $length = $this->intConfig('code_length', 6);
        $last = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $last)];
        }

        return $code;
    }

    public function whatsAppLink(string $code): string
    {
        $configured = preg_replace('/\D/', '', (string) config('services.whatsapp.business_number')) ?? '';
        $digits = $configured !== '' ? $configured : '256793844906';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(self::MESSAGE_PREFIX.$code);
    }

    /**
     * Inbound WhatsApp message. True when this number had a verification
     * attempt we answered. A different number returns false and sends nothing
     * from this service.
     */
    public function handleInbound(string $from, string $body): bool
    {
        $digits = $this->fullInternationalDigits($from);
        if ($digits === null || ! $this->tableReady()) {
            return false;
        }

        $outcome = DB::transaction(function () use ($digits, $body) {
            $rows = WhatsAppVerificationChallenge::query()
                ->where('phone_digits', $digits)
                ->where('created_at', '>=', now()->subHours(2))
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                return null;
            }

            $active = $rows->first(fn (WhatsAppVerificationChallenge $row) => $this->isActive($row));
            $match = $rows->first(fn (WhatsAppVerificationChallenge $row) => $this->bodyHasCode($body, $row));

            if ($match !== null && $match->used_at === null && $this->isActive($match)) {
                if ($this->markVerified($match)) {
                    return ['reply' => 'KlassApp verified this number. You can return to setup.', 'user_id' => (int) $match->user_id, 'phone' => (string) $match->phone, 'outcome' => 'verified'];
                }

                return ['reply' => 'This number is already registered to another account.', 'user_id' => (int) $match->user_id, 'phone' => (string) $match->phone, 'outcome' => 'refused'];
            }

            if ($match !== null && $match->used_at !== null) {
                return ['reply' => 'That code was already used. Get a new code in KlassApp.', 'user_id' => (int) $match->user_id, 'phone' => (string) $match->phone, 'outcome' => 'reused'];
            }

            if ($match !== null && $this->isExpired($match)) {
                $match->forceFill(['used_at' => $match->used_at ?? now()])->save();

                return ['reply' => self::EXPIRED_MESSAGE, 'user_id' => (int) $match->user_id, 'phone' => (string) $match->phone, 'outcome' => 'expired'];
            }

            if (! $this->looksLikeAttempt($body) || $active === null) {
                return null;
            }

            $attempts = (int) $active->attempts + 1;
            $max = $this->intConfig('max_attempts', 5);
            if ($attempts >= $max) {
                $active->forceFill(['attempts' => $attempts, 'used_at' => now()])->save();

                return ['reply' => 'That code is no longer valid. Get a new code in KlassApp.', 'user_id' => (int) $active->user_id, 'phone' => (string) $active->phone, 'outcome' => 'exhausted'];
            }

            $active->forceFill(['attempts' => $attempts])->save();

            return ['reply' => 'That code does not match. Check the code in KlassApp and send it again.', 'user_id' => (int) $active->user_id, 'phone' => (string) $active->phone, 'outcome' => 'mismatch'];
        });

        if ($outcome === null) {
            return false;
        }

        Log::info('WhatsApp verification inbound', [
            'phone' => WhatsAppPhoneHelper::mask((string) $outcome['phone']),
            'user_id' => (int) $outcome['user_id'],
            'outcome' => (string) $outcome['outcome'],
        ]);

        $this->reply((string) $outcome['phone'], (string) $outcome['reply'], (int) $outcome['user_id']);

        return true;
    }

    /**
     * @return array{verified: bool, expired: bool, phone: string}
     */
    public function statusFor(int $adminUserId, string $phone): array
    {
        $canonical = $this->canonicalPhone($phone);
        $verified = false;
        if ($canonical !== null) {
            $verified = WhatsAppUser::query()
                ->where('user_id', $adminUserId)
                ->where('phone', $canonical)
                ->whereNotNull('verified_at')
                ->exists();
        }

        $expired = false;
        if (! $verified && $canonical !== null && $this->tableReady()) {
            $latest = WhatsAppVerificationChallenge::query()
                ->where('user_id', $adminUserId)
                ->where('phone', $canonical)
                ->orderByDesc('id')
                ->first();
            $expired = $latest !== null
                && $latest->used_at === null
                && $this->isExpired($latest)
                && ! $verified;
        }

        return [
            'verified' => $verified,
            'expired' => $expired && ! $verified,
            'phone' => $canonical ?? trim($phone),
        ];
    }

    /**
     * Redact a verification code before it is written to a log or a delivery preview.
     */
    public function redact(string $body): string
    {
        $length = $this->intConfig('code_length', 6);
        $redacted = preg_replace(
            '/(Verify KlassApp:\s*)['.self::ALPHABET.']{'.$length.'}/i',
            '$1******',
            $body
        ) ?? $body;

        if (preg_match('/\A\s*['.self::ALPHABET.']{'.$length.'}\s*\z/i', $redacted) === 1) {
            return '******';
        }

        foreach ($this->candidateCodes($body) as $candidate) {
            if ($this->matchesAnyStoredHash($candidate)) {
                $redacted = str_ireplace($candidate, '******', $redacted);
            }
        }

        return $redacted;
    }

    public function canonicalPhone(string $phone): ?string
    {
        $digits = $this->digits($phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '' || strlen($digits) < 11 || strlen($digits) > 15 || str_starts_with($digits, '0')) {
            return null;
        }

        return '+'.$digits;
    }

    private function markVerified(WhatsAppVerificationChallenge $challenge): bool
    {
        $user = User::query()
            ->whereKey($challenge->user_id)
            ->where('school_id', $challenge->school_id)
            ->where('status', 'active')
            ->first();

        if ($user === null) {
            $challenge->forceFill(['used_at' => now()])->save();

            return false;
        }

        $existingForPhone = WhatsAppUser::query()->where('phone', $challenge->phone)->first();
        if ($existingForPhone !== null && $existingForPhone->user_id !== null && (int) $existingForPhone->user_id !== (int) $user->id) {
            $challenge->forceFill(['used_at' => now()])->save();

            return false;
        }

        $own = WhatsAppUser::query()->where('user_id', $user->id)->first();
        if ($own !== null && $existingForPhone !== null && (int) $own->id !== (int) $existingForPhone->id) {
            $challenge->forceFill(['used_at' => now()])->save();

            return false;
        }

        $row = $own ?? $existingForPhone ?? new WhatsAppUser;

        try {
            $row->fill([
                'phone' => $challenge->phone,
                'user_id' => $user->id,
                'school_id' => (int) $challenge->school_id,
                'opted_in' => true,
                'verified_at' => $row->verified_at ?? now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            $challenge->forceFill(['used_at' => now()])->save();

            return false;
        }

        $challenge->forceFill(['used_at' => now()])->save();

        return $row->verified_at !== null;
    }

    private function isActive(WhatsAppVerificationChallenge $row): bool
    {
        return $row->used_at === null && ! $this->isExpired($row);
    }

    private function isExpired(WhatsAppVerificationChallenge $row): bool
    {
        return $row->expires_at->utc()->getTimestamp() <= now()->utc()->getTimestamp();
    }

    private function bodyHasCode(string $body, WhatsAppVerificationChallenge $row): bool
    {
        foreach ($this->candidateCodes($body) as $candidate) {
            if (hash_equals((string) $row->code_hash, $this->hashCode($candidate))) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeAttempt(string $body): bool
    {
        if (stripos($body, 'Verify KlassApp:') !== false) {
            return true;
        }

        $length = $this->intConfig('code_length', 6);

        return preg_match('/\A\s*['.self::ALPHABET.']{'.$length.'}\s*\z/i', trim($body)) === 1;
    }

    /**
     * @return list<string>
     */
    private function candidateCodes(string $body): array
    {
        $length = $this->intConfig('code_length', 6);
        $pattern = '/['.preg_quote(self::ALPHABET, '/').']{'.$length.'}/i';
        if (preg_match_all($pattern, $body, $matches) < 1) {
            return [];
        }

        $codes = [];
        foreach ($matches[0] as $token) {
            $codes[] = strtoupper($token);
            if (count($codes) >= 8) {
                break;
            }
        }

        return array_values(array_unique($codes));
    }

    private function matchesAnyStoredHash(string $code): bool
    {
        if (! $this->tableReady()) {
            return false;
        }

        $hash = $this->hashCode($code);
        $stored = WhatsAppVerificationChallenge::query()
            ->where('created_at', '>=', now()->subHours(2))
            ->pluck('code_hash');

        foreach ($stored as $rowHash) {
            if (hash_equals((string) $rowHash, $hash)) {
                return true;
            }
        }

        return false;
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', strtoupper($code), (string) config('app.key'));
    }

    private function fullInternationalDigits(string $phone): ?string
    {
        $canonical = $this->canonicalPhone($phone);

        return $canonical === null ? null : ltrim($canonical, '+');
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    private function phoneRateKey(string $canonical): string
    {
        return 'wa-user-verify:phone:'.hash('sha256', $canonical);
    }

    private function ipRateKey(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return null;
        }

        return 'wa-user-verify:ip:'.hash('sha256', $ip);
    }

    /**
     * Production always limits. The test suite opts in, because every wizard
     * request shares 127.0.0.1 and the file cache would carry the count over.
     */
    private function tableReady(): bool
    {
        return Schema::hasTable('whatsapp_verification_challenges');
    }

    private function limitsEnforced(): bool
    {
        if (! app()->environment('testing')) {
            return true;
        }

        return (bool) config('services.whatsapp.user_initiated_verification.enforce_limits_in_tests', false);
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config('services.whatsapp.user_initiated_verification.'.$key, $default);

        return max(1, (int) $value);
    }

    private function reply(string $phone, string $text, int $userId): void
    {
        if (! $this->whatsApp->isConfigured()) {
            return;
        }

        try {
            $this->whatsApp->sendText($phone, $text, 'service', $userId);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp verification reply failed', [
                'phone' => WhatsAppPhoneHelper::mask($phone),
            ]);
        }
    }
}
