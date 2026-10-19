<?php

namespace App\Services;

use App\Mail\SchoolInviteMail;
use App\Models\EarlyAccessRequest;
use App\Models\SchoolInvite;
use App\Models\User;
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
    public static function issue(EarlyAccessRequest $request, User $issuedBy): array
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
        ]);

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
        if ($invite->isClaimed()) {
            return ['success' => false, 'message' => 'This invite has already been used. Sign in with the email it was sent to.'];
        }

        if ($invite->isRevoked()) {
            return ['success' => false, 'message' => 'This invite has been revoked. Ask KlassApp for a new one.'];
        }

        if ($invite->isExpired()) {
            return ['success' => false, 'message' => 'This invite has expired. Ask KlassApp for a new one.'];
        }

        $email = mb_strtolower(trim((string) $invite->email));

        if (User::withTrashed()->where('email', $email)->exists()) {
            return ['success' => false, 'message' => 'An account with this email already exists. Sign in instead.'];
        }

        try {
            $user = DB::transaction(function () use ($invite, $data, $email) {
                $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
                    'name' => trim((string) $data['name']),
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                    'school_name' => $invite->school_name,
                    'registration_country' => $invite->country,
                ]);

                $invite->forceFill(['claimed_at' => now()])->save();

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('School invite claim failed', [
                'invite_id' => $invite->id,
                'email' => $email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'We could not create the school right now. Please try again.'];
        }

        return ['success' => true, 'message' => 'Your school is ready.', 'user' => $user];
    }

    public static function inviteUrl(string $token): string
    {
        return url('/invite/school/'.$token);
    }

    public static function sendEmail(SchoolInvite $invite, string $token): void
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
        }
    }
}
