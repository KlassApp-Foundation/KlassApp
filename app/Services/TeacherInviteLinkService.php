<?php

namespace App\Services;

use App\Mail\TeacherInviteLinkMail;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Issues one-time teacher invite links and handles token verification + claiming.
 *
 * Tokens are 64 random chars, stored as SHA-256 hashes (not plain text).
 * Links expire after 72 hours and are single-use only.
 */
class TeacherInviteLinkService
{
    public const TOKEN_LENGTH = 64;

    public const EXPIRY_HOURS = 72;

    /**
     * Issue a new invite for a new teacher.
     *
     * @return array{invite: TeacherInvite, token: string} The raw token is returned
     *                                               once so the caller can build the link — it is never stored or logged.
     */
    public static function issue(
        School $school,
        string $email,
        string $name,
        ?StandardLink $standardLink = null,
        ?string $phone = null,
    ): array {
        $token = Str::random(self::TOKEN_LENGTH);
        $tokenHash = hash('sha256', $token);

        $invite = TeacherInvite::create([
            'school_id'        => $school->id,
            'email'            => mb_strtolower(trim($email)),
            'token_hash'       => $tokenHash,
            'role'             => 'class_teacher',
            'standard_link_id' => $standardLink?->id,
            'name'             => trim($name),
            'phone'            => $phone ? trim($phone) : null,
            'expires_at'       => now()->addHours(self::EXPIRY_HOURS),
        ]);

        Log::info('Teacher invite issued', [
            'invite_id'   => $invite->id,
            'school_id'   => $school->id,
            'email'       => $invite->email,
            'expires_at'  => $invite->expires_at->toIso8601String(),
            // token is never logged
        ]);

        return ['invite' => $invite, 'token' => $token];
    }

    /**
     * Build the absolute invite URL from a raw token.
     */
    public static function inviteUrl(string $token): string
    {
        return url('/invite/teacher/'.$token);
    }

    /**
     * Send the invite via email.
     */
    public static function sendEmail(TeacherInvite $invite, string $token, School $school, ?string $className = null): void
    {
        $url = self::inviteUrl($token);

        try {
            \Mail::to($invite->email)->queue(new TeacherInviteLinkMail(
                name: $invite->name ?? 'Teacher',
                schoolName: $school->name ?? 'KlassApp',
                inviteUrl: $url,
                className: $className,
                expiresAt: $invite->expires_at,
            ));
        } catch (\Exception $e) {
            Log::warning('Teacher invite: email failed', [
                'invite_id' => $invite->id,
                'email'     => $invite->email,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send the invite via WhatsApp when a phone number is available and WABA is configured.
     */
    public static function sendWhatsApp(TeacherInvite $invite, string $token, School $school, ?string $className = null): void
    {
        $phone = $invite->phone;
        if (! $phone || trim($phone) === '') {
            return;
        }

        $url = self::inviteUrl($token);
        $classLabel = $className ? " for {$className}" : '';
        $message = "You've been invited to join {$school->name}{$classLabel} on KlassApp. "
                 ."Set your password here: {$url} "
                 ."(link expires in ".self::EXPIRY_HOURS.' hours)';

        try {
            $whatsApp = app(\App\Services\WhatsAppBusinessService::class);
            $whatsApp->sendText($phone, $message, 'teacher_invite', null);
        } catch (\Exception $e) {
            Log::warning('Teacher invite: WhatsApp send failed', [
                'invite_id' => $invite->id,
                'phone'     => $phone,
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
        $invite = TeacherInvite::with('school', 'standardLink.section')
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
     * Claim an invite: create the User with the teacher's chosen password,
     * mark the invite as claimed, and assign class_teacher_id.
     *
     * Password strength is validated by the caller (controller).
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public static function claim(
        TeacherInvite $invite,
        string $password,
    ): array {
        if ($invite->isClaimed()) {
            return ['success' => false, 'message' => 'This invite has already been used.'];
        }

        if ($invite->isExpired()) {
            return ['success' => false, 'message' => 'This invite has expired.'];
        }

        try {
            DB::beginTransaction();

            // Create the teacher
            $teacher = User::create([
                'school_id'      => $invite->school_id,
                'usergroup_id'   => 5,
                'name'           => $invite->name ?? 'Teacher',
                'email'          => $invite->email,
                'password'       => bcrypt($password),
                'is_reset'       => 0,
                'status'         => 'active',
                'email_verified' => 1,
                'mobile_no'      => $invite->phone,
            ]);

            Userprofile::create([
                'school_id'    => $invite->school_id,
                'user_id'      => $teacher->id,
                'usergroup_id' => 5,
                'firstname'    => $invite->name ?? 'Teacher',
                'lastname'     => '',
                'status'       => 'active',
                'alternate_no' => $invite->phone,
            ]);

            // Assign class teacher if applicable
            if ($invite->standard_link_id) {
                $standardLink = StandardLink::find($invite->standard_link_id);
                if ($standardLink) {
                    $standardLink->class_teacher_id = $teacher->id;
                    $standardLink->save();

                    $section = $standardLink->section;
                    if ($section) {
                        $section->class_teacher_id = $teacher->id;
                        $section->save();
                    }
                }
            }

            // Mark invite claimed
            $invite->user_id = $teacher->id;
            $invite->claimed_at = now();
            $invite->save();

            DB::commit();

            Log::info('Teacher invite claimed', [
                'invite_id'  => $invite->id,
                'teacher_id' => $teacher->id,
                'school_id'  => $invite->school_id,
                'email'      => $invite->email,
            ]);

            return [
                'success' => true,
                'message' => 'Account created. You can now log in.',
                'user'    => $teacher,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Teacher invite: claim failed', [
                'invite_id' => $invite->id,
                'email'     => $invite->email,
                'error'     => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Could not create your account. Please try again.'];
        }
    }
}
