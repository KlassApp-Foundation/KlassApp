<?php

namespace App\Services;

use App\Mail\CoAdminInviteLinkMail;
use App\Models\CoAdminInvite;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Issues one-time co-admin invite links and handles token verification + claiming.
 *
 * Tokens are 64 random chars, stored as SHA-256 hashes (not plain text).
 * Links expire after 72 hours and are single-use only.
 */
class CoAdminInviteLinkService
{
    public const TOKEN_LENGTH = 64;

    public const EXPIRY_HOURS = 72;

    /**
     * Issue a new invite for a new co-admin.
     *
     * @return array{invite: CoAdminInvite, token: string} The raw token is returned
     *                                                     once so the caller can build the link — it is never stored or logged.
     */
    public static function issue(School $school, string $email, string $name): array
    {
        $token = Str::random(self::TOKEN_LENGTH);
        $tokenHash = hash('sha256', $token);

        $invite = CoAdminInvite::create([
            'school_id'  => $school->id,
            'email'      => mb_strtolower(trim($email)),
            'token_hash' => $tokenHash,
            'name'       => trim($name),
            'expires_at' => now()->addHours(self::EXPIRY_HOURS),
        ]);

        Log::info('Co-admin invite issued', [
            'invite_id'  => $invite->id,
            'school_id'  => $school->id,
            'email'      => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
            // token is never logged
        ]);

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * Reissue an invite: generates a fresh token (invalidating the previous one)
     * and extends the expiry window. Used by the resend action.
     *
     * @return array{invite: CoAdminInvite, token: string}
     */
    public static function reissue(CoAdminInvite $invite): array
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $invite->token_hash = hash('sha256', $token);
        $invite->expires_at = now()->addHours(self::EXPIRY_HOURS);
        $invite->save();

        Log::info('Co-admin invite reissued', [
            'invite_id'  => $invite->id,
            'school_id'  => $invite->school_id,
            'email'      => $invite->email,
            'expires_at' => $invite->expires_at->toIso8601String(),
            // token is never logged
        ]);

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * Pending (unclaimed) invites for a school, newest first.
     */
    public static function pending(School $school): Collection
    {
        return CoAdminInvite::where('school_id', $school->id)
            ->whereNull('claimed_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Build the absolute invite URL from a raw token.
     */
    public static function inviteUrl(string $token): string
    {
        return url('/invite/co-admin/'.$token);
    }

    /**
     * Send the invite via email.
     */
    public static function sendEmail(CoAdminInvite $invite, string $token, School $school): void
    {
        try {
            \Mail::to($invite->email)->queue(new CoAdminInviteLinkMail(
                name: $invite->name ?? 'Co-Admin',
                schoolName: $school->name ?? 'KlassApp',
                inviteUrl: self::inviteUrl($token),
                expiresAt: $invite->expires_at,
            ));
        } catch (\Exception $e) {
            Log::warning('Co-admin invite: email failed', [
                'invite_id' => $invite->id,
                'email'     => $invite->email,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find and validate a token. Returns the invite + school if valid, or null.
     *
     * Validation:
     *  - Token must exist (hash matches a row)
     *  - Invite must not be expired
     *  - Invite must not already be claimed
     */
    public static function validateToken(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        $invite = CoAdminInvite::with('school')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $invite) {
            return null;
        }

        if ($invite->isExpired()) {
            return ['invite' => $invite, 'school' => $invite->school, 'error' => 'expired'];
        }

        if ($invite->isClaimed()) {
            return ['invite' => $invite, 'school' => $invite->school, 'error' => 'claimed'];
        }

        return ['invite' => $invite, 'school' => $invite->school, 'error' => null];
    }

    /**
     * Claim an invite: create the User with the co-admin's chosen password,
     * create the Userprofile, and mark the invite as claimed.
     *
     * Password strength is validated by the caller (controller).
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public static function claim(CoAdminInvite $invite, string $password): array
    {
        if ($invite->isClaimed()) {
            return ['success' => false, 'message' => 'This invite has already been used.'];
        }

        if ($invite->isExpired()) {
            return ['success' => false, 'message' => 'This invite has expired.'];
        }

        try {
            DB::beginTransaction();

            $coAdmin = User::create([
                'school_id'      => $invite->school_id,
                'usergroup_id'   => 3,
                'name'           => $invite->name ?? 'Co-Admin',
                'email'          => $invite->email,
                'password'       => bcrypt($password),
                'is_reset'       => 0,
                'status'         => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id'    => $invite->school_id,
                'user_id'      => $coAdmin->id,
                'usergroup_id' => 3,
                'firstname'    => $invite->name ?? 'Co-Admin',
                'lastname'     => 'Co-Admin',
                'status'       => 'active',
            ]);

            $invite->user_id = $coAdmin->id;
            $invite->claimed_at = now();
            $invite->save();

            DB::commit();

            Log::info('Co-admin invite claimed', [
                'invite_id' => $invite->id,
                'user_id'   => $coAdmin->id,
                'school_id' => $invite->school_id,
                'email'     => $invite->email,
            ]);

            return [
                'success' => true,
                'message' => 'Account created. You can now log in.',
                'user'    => $coAdmin,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Co-admin invite: claim failed', [
                'invite_id' => $invite->id,
                'email'     => $invite->email,
                'error'     => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Could not create your account. Please try again.'];
        }
    }
}
