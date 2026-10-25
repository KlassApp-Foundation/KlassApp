<?php

namespace App\Services;

use App\Mail\SchoolInviteMail;
use App\Models\EarlyAccessRequest;
use App\Models\SchoolInvite;
use App\Models\User;
use App\Services\EarlyAccessOpsWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Issues, resends, revokes, validates and claims one-time early-adopter invites.
 *
 * Tokens are 64 random characters, stored as SHA-256 hashes. A token is
 * email-bound, single-use and expires after invites.school_expiry_hours
 * (7 days by default). The invite is the only sign-up path while
 * SIGNUP_ENABLED=false; it calls bootstrapAsOperator() after validation.
 */
class SchoolInviteService
{
    public const TOKEN_LENGTH = 64;

    /**
     * @return array{invite: SchoolInvite, token: string}
     */
    public static function issue(EarlyAccessRequest $request, User $issuedBy, bool $isTest = false): array
    {
        $token = Str::random(self::TOKEN_LENGTH);
        $tokenHash = hash('sha256', $token);

        $invite = SchoolInvite::create([
            'email' => mb_strtolower(trim((string) $request->email)),
            'school_name' => trim((string) $request->school_name),
            'contact_name' => trim((string) $request->contact_name) ?: null,
            'country' => trim((string) $request->country) ?: null,
            'approx_students' => $request->approx_students ? (int) $request->approx_students : null,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours((int) config('invites.school_expiry_hours', 168)),
            'created_by' => $issuedBy->id,
            'is_test' => $isTest,
            'early_access_request_id' => $request->id,
        ]);

        // Issuing the invite is the approval. The invite email says the
        // request has been approved, including when that email cannot be queued.
        app(EarlyAccessOpsWebhook::class)->queue($request, 'approved');

        Log::info('School invite issued', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
            // token is never logged
        ]);

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * @return array{invite: SchoolInvite, token: string}
     */
    public static function reissue(SchoolInvite $invite): array
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $invite->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours((int) config('invites.school_expiry_hours', 168)),
            'revoked_at' => null,
        ])->save();

        Log::info('School invite reissued', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
        ]);

        return ['invite' => $invite, 'token' => $token];
    }

    public static function revoke(SchoolInvite $invite): void
    {
        if ($invite->isClaimed()) {
            return;
        }

        $invite->forceFill(['revoked_at' => now()])->save();

        Log::info('School invite revoked', ['invite_id' => $invite->id, 'email' => $invite->email]);
    }

    /**
     * @return array{invite: SchoolInvite|null, error: string|null}
     */
    public static function validateToken(string $rawToken): array
    {
        $token = trim($rawToken);
        if ($token === '') {
            return ['invite' => null, 'error' => 'invalid'];
        }

        $invite = SchoolInvite::where('token_hash', hash('sha256', $token))->first();
        if ($invite === null) {
            return ['invite' => null, 'error' => 'invalid'];
        }

        if ($invite->isClaimed()) {
            return ['invite' => $invite, 'error' => 'claimed'];
        }

        if ($invite->isRevoked()) {
            return ['invite' => $invite, 'error' => 'revoked'];
        }

        if ($invite->isExpired()) {
            return ['invite' => $invite, 'error' => 'expired'];
        }

        return ['invite' => $invite, 'error' => null];
    }

    /**
     * @param  array{name: string, phone: string, password: string}  $data
     * @return array{success: bool, message: string, user?: User}
     */
    public static function claim(SchoolInvite $invite, array $data): array
    {
        // The claim request cannot choose this. The school flag is copied
        // from the invite row, which only a platform admin can set.
        unset($data['is_test']);

        if ($invite->isRevoked()) {
            return ['success' => false, 'message' => 'This invite has been revoked. Ask KlassApp for a new one.'];
        }

        if ($invite->isExpired()) {
            return ['success' => false, 'message' => 'This invite has expired. Ask KlassApp for a new one.'];
        }

        $email = mb_strtolower(trim((string) $invite->email));
        $alreadyClaimed = 'This invite has already been used. Sign in with the email it was sent to.';

        try {
            $result = DB::transaction(function () use ($invite, $data, $email, $alreadyClaimed) {
                // Lock the invite row and re-check it, so two simultaneous
                // submissions of the same invite cannot both create a school.
                $locked = SchoolInvite::whereKey($invite->id)->lockForUpdate()->first();

                if ($locked === null || $locked->isClaimed()) {
                    return ['success' => false, 'message' => $alreadyClaimed];
                }

                // Mark claimed before creating anything. The whereNull guard
                // also holds on drivers without row locks (SQLite in tests).
                $claimedAt = now();
                $marked = SchoolInvite::whereKey($locked->id)
                    ->whereNull('claimed_at')
                    ->update(['claimed_at' => $claimedAt, 'updated_at' => $claimedAt]);

                if ($marked !== 1) {
                    return ['success' => false, 'message' => $alreadyClaimed];
                }

                if (User::withTrashed()->where('email', $email)->exists()) {
                    throw new \DomainException('email_exists');
                }

                $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
                    'name' => trim((string) $data['name']),
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                    'school_name' => $locked->school_name,
                    'registration_country' => $locked->country,
                    'is_test' => (bool) $locked->is_test,
                ]);

                // School invites are issued only from an early-access request.
                // Teacher, co-admin, and class-teacher invites never reach here.
                app(EarlyAdopterOffer::class)->apply($user->school, $user, $claimedAt);

                $linked = self::earlyAccessRequestFor($locked, 'claimed');
                if ($linked !== null) {
                    // afterCommit: a rollback of this claim does not notify ops.
                    app(EarlyAccessOpsWebhook::class)->queue($linked, 'claimed');
                }

                return ['success' => true, 'message' => 'Your school is ready.', 'user' => $user];
            });
        } catch (Throwable $e) {
            if ($e instanceof \DomainException && $e->getMessage() === 'email_exists') {
                // Rolled back: the invite stays unclaimed.
                return ['success' => false, 'message' => 'An account with this email already exists. Sign in instead.'];
            }

            Log::error('School invite claim failed', [
                'invite_id' => $invite->id,
                'email' => $email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'We could not create the school right now. Please try again.'];
        }

        if ($result['success']) {
            $invite->refresh();
        }

        return $result;
    }

    public static function inviteUrl(string $token): string
    {
        return url('/invite/school/'.$token);
    }

    /**
     * Queue the invite email. Returns false when it could not be queued, and
     * records that on the invite so the admin page can offer a retry.
     */
    public static function sendEmail(SchoolInvite $invite, string $token): bool
    {
        try {
            Mail::to($invite->email)->queue(new SchoolInviteMail(
                name: $invite->contact_name ?: 'there',
                schoolName: $invite->school_name,
                inviteUrl: self::inviteUrl($token),
                expiresAt: $invite->expires_at,
            ));
        } catch (Throwable $e) {
            Log::warning('School invite email failed', [
                'invite_id' => $invite->id,
                'email' => $invite->email,
                'error' => $e->getMessage(),
            ]);

            $invite->forceFill(['email_failed_at' => now()])->save();

            return false;
        }

        if ($invite->email_failed_at !== null) {
            $invite->forceFill(['email_failed_at' => null])->save();
        }

        $linked = self::earlyAccessRequestFor($invite, 'invited');
        if ($linked !== null) {
            app(EarlyAccessOpsWebhook::class)->queue($linked, 'invited');
        }

        return true;
    }

    /**
     * The request this invite was issued from. One email match is used only
     * when the invite has no request id (invites issued before that column).
     * Two matches are left alone.
     */
    private static function earlyAccessRequestFor(SchoolInvite $invite, string $event): ?EarlyAccessRequest
    {
        if ($invite->early_access_request_id) {
            $found = EarlyAccessRequest::query()->find($invite->early_access_request_id);
            if ($found !== null) {
                return $found;
            }
        }

        $email = mb_strtolower(trim((string) $invite->email));
        if ($email === '') {
            return null;
        }

        $ids = EarlyAccessRequest::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');

        if ($ids->count() === 1) {
            return EarlyAccessRequest::query()->find($ids->first());
        }

        if ($ids->count() > 1 && trim((string) config('services.ops_webhook.url')) !== '') {
            Log::warning('Early access ops webhook skipped: invite does not match one request', [
                'invite_id' => $invite->id,
                'event' => $event,
            ]);
        }

        return null;
    }
}
