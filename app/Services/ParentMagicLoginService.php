<?php

namespace App\Services;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Signed, short-lived magic links for ug7 parents to open the web dashboard.
 */
class ParentMagicLoginService
{
    public const TTL_MINUTES = 15;

    public const RATE_LIMIT_PER_HOUR = 5;

    public function canIssueLink(User $parent): bool
    {
        return (int) $parent->usergroup_id === 7 && $this->hasActiveLinks($parent);
    }

    public function issueLinkForPhone(string $phone, User $parent): ?string
    {
        if (! $this->canIssueLink($parent)) {
            return null;
        }

        $rateKey = self::rateLimitKey($phone);
        if (RateLimiter::tooManyAttempts($rateKey, self::RATE_LIMIT_PER_HOUR)) {
            return null;
        }

        RateLimiter::hit($rateKey, 3600);

        $nonce = Str::random(40);

        return URL::temporarySignedRoute(
            'parent.magic-login',
            now()->addMinutes(self::TTL_MINUTES),
            ['user' => $parent->id, 'nonce' => $nonce],
        );
    }

    public function isNonceConsumed(string $nonce): bool
    {
        return Cache::has($this->nonceCacheKey($nonce));
    }

    /**
     * Mark nonce as used (single-use). Returns false if already consumed.
     */
    public function consumeNonce(string $nonce): bool
    {
        return Cache::add(
            $this->nonceCacheKey($nonce),
            true,
            now()->addMinutes(self::TTL_MINUTES + 1),
        );
    }

    public static function rateLimitKey(string $phone): string
    {
        return 'parent-magic-login:'.WhatsAppPhoneHelper::normalise($phone);
    }

    private function hasActiveLinks(User $parent): bool
    {
        return StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', 1)
            ->whereNotNull('school_id')
            ->exists();
    }

    private function nonceCacheKey(string $nonce): string
    {
        return 'parent_magic_login_used:'.$nonce;
    }
}
