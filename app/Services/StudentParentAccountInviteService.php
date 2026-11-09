<?php

namespace App\Services;

use App\Mail\StudentParentSetupLinkMail;
use App\Models\School;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Support\PendingPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Setup links for parents and students the school has already created.
 *
 * The token, its hash, its expiry and the hourly issuing cap come from
 * TeacherInviteLinkService so every invite in the product follows the same
 * rules. This service only decides when a parent or student gets one and how
 * the person finishes setup on the account that already exists.
 *
 * No password is ever generated, stored or emailed here: the person chooses
 * one on the link.
 */
class StudentParentAccountInviteService
{
    /**
     * Student or parent, or null for every other account type.
     */
    public function roleFor(User $person): ?string
    {
        return match ((int) $person->usergroup_id) {
            6 => 'student',
            7 => 'parent',
            default => null,
        };
    }

    /**
     * Same address rule as a teacher invite: a placeholder address is stored
     * on the row but is not something a person can receive mail at.
     */
    public function emailIsUsable(?string $email): bool
    {
        return app(TeacherAccountInviteService::class)->emailIsUsable($email);
    }

    public function hasSignedIn(User $person): bool
    {
        return app(TeacherAccountInviteService::class)->hasSignedIn($person);
    }

    /**
     * A resend is only for a parent or student who has never signed in and
     * has a real address to send the link to.
     */
    public function canResend(User $person): bool
    {
        return $this->roleFor($person) !== null
            && $person->status === 'active'
            && $this->emailIsUsable($person->email)
            && ! $this->hasSignedIn($person);
    }

    /**
     * Issue a setup link for an existing parent or student and email it.
     * A placeholder address never sends anything.
     *
     * @return array{invited: bool, reason: string}
     */
    public function inviteIfNeeded(
        User $person,
        ?User $actor = null,
        bool $consumeLimit = true,
        string $context = TeacherInviteLinkService::CONTEXT_INTERACTIVE,
    ): array {
        // A row created in this request may not have read back the database
        // defaults for status and usergroup yet.
        if ($person->status === null || $person->usergroup_id === null) {
            $person->refresh();
        }

        $role = $this->roleFor($person);
        if ($role === null) {
            return ['invited' => false, 'reason' => 'not_parent_or_student'];
        }

        if ($person->status !== 'active') {
            return ['invited' => false, 'reason' => 'not_active'];
        }

        if ($this->hasSignedIn($person)) {
            return ['invited' => false, 'reason' => 'signed_in'];
        }

        if (! $this->emailIsUsable($person->email)) {
            return ['invited' => false, 'reason' => 'no_email'];
        }

        $actor = $this->actorForPerson($actor, $person);
        if ($consumeLimit) {
            $key = hash('sha256', mb_strtolower((string) $person->email).'|'.$person->id);
            if (TeacherInviteLinkService::issueIsLimited($actor, $key, $context)) {
                return ['invited' => false, 'reason' => 'limited'];
            }
        }

        $school = School::query()->whereKey((int) $person->school_id)->first();
        if (! $school instanceof School) {
            return ['invited' => false, 'reason' => 'no_school'];
        }

        $email = mb_strtolower(trim((string) $person->email));
        $this->revokeOpenInvites((int) $school->id, $email, (int) $person->id);

        // An imported account can still carry the random hash older imports
        // stored. It was never shown to anyone, so clear it: this account has
        // not finished setup until the link is used.
        if ((int) $person->is_reset === 1 && ! PendingPassword::isUnset($person)) {
            $person->forceFill(['password' => null])->save();
        }

        $issued = TeacherInviteLinkService::issue(
            school: $school,
            email: $email,
            name: trim((string) $person->name) !== '' ? trim((string) $person->name) : ucfirst($role),
            standardLink: null,
            phone: filled($person->mobile_no) ? (string) $person->mobile_no : null,
        );

        // The token, hash, expiry and cap are the teacher service's. Only the
        // role and the existing account this link belongs to differ.
        $issued['invite']->user_id = $person->id;
        $issued['invite']->role = $role;
        $issued['invite']->save();

        $this->sendEmail($issued['invite'], $issued['token'], $school, $role);

        Log::info('Student or parent setup link issued', [
            'invite_id' => $issued['invite']->id,
            'school_id' => $school->id,
            'user_id' => $person->id,
            'role' => $role,
        ]);

        return ['invited' => true, 'reason' => 'sent'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function resend(User $actor, User $person): array
    {
        if ((int) $actor->school_id === 0 || (int) $actor->school_id !== (int) $person->school_id) {
            return ['ok' => false, 'message' => 'Person not found or not in your school.'];
        }

        $role = $this->roleFor($person);
        if ($role === null) {
            return ['ok' => false, 'message' => 'Person not found or not in your school.'];
        }

        $label = $role === 'parent' ? 'parent' : 'student';

        if ($this->hasSignedIn($person)) {
            return ['ok' => false, 'message' => "This {$label} has already signed in."];
        }

        if (! $this->emailIsUsable($person->email)) {
            return ['ok' => false, 'message' => "This {$label} has no email yet. Add one before they can be invited."];
        }

        $result = $this->inviteIfNeeded($person, $actor);
        if ($result['reason'] === 'limited') {
            return ['ok' => false, 'message' => 'Too many setup links. Wait an hour and try again.'];
        }

        if (! ($result['invited'] ?? false)) {
            return ['ok' => false, 'message' => 'Could not send a setup link.'];
        }

        return ['ok' => true, 'message' => 'A new setup link was sent. The previous link no longer works.'];
    }

    /**
     * The account this invite belongs to, or null when the link no longer
     * matches one. The address on the invite must still be the address on
     * the account, so a changed email does not keep an old link alive.
     */
    public function personForInvite(TeacherInvite $invite): ?User
    {
        $role = (string) $invite->role;
        $group = match ($role) {
            'student' => 6,
            'parent' => 7,
            default => null,
        };

        if ($group === null || ! $invite->user_id) {
            return null;
        }

        $person = User::query()
            ->where('school_id', (int) $invite->school_id)
            ->where('usergroup_id', $group)
            ->whereKey((int) $invite->user_id)
            ->first();

        if (! $person instanceof User) {
            return null;
        }

        if (mb_strtolower(trim((string) $person->email)) !== mb_strtolower(trim((string) $invite->email))) {
            return null;
        }

        if ($person->status !== 'active') {
            return null;
        }

        return $person;
    }

    /**
     * Set the chosen password on the existing account and claim the link.
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public function completeClaim(TeacherInvite $invite, string $password): array
    {
        $person = $this->personForInvite($invite);
        if (! $person instanceof User) {
            return ['success' => false, 'message' => 'This setup link no longer matches an account.'];
        }

        if ($this->hasSignedIn($person)) {
            return ['success' => false, 'message' => 'This account is already set up. Sign in instead.'];
        }

        try {
            DB::transaction(function () use ($invite, $person, $password): void {
                $claimed = TeacherInvite::query()
                    ->whereKey($invite->id)
                    ->whereNull('claimed_at')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if ($claimed === null) {
                    throw new \RuntimeException('This setup link has already been used or has expired.');
                }

                $claimed->user_id = $person->id;
                $claimed->claimed_at = now();
                $claimed->save();

                $person->forceFill([
                    'password' => Hash::make($password),
                    'is_reset' => 0,
                    'email_verified' => 1,
                    'status' => 'active',
                ])->save();

                TeacherInvite::query()
                    ->where('school_id', (int) $claimed->school_id)
                    ->where('user_id', (int) $person->id)
                    ->whereKeyNot($claimed->id)
                    ->whereNull('claimed_at')
                    ->update(['revoked_at' => now()]);
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'This setup link has already been used or has expired.'];
        }

        return [
            'success' => true,
            'message' => 'Account set up. Sign in with your email and the password you just set.',
            'user' => $person->fresh(),
        ];
    }

    public function setupUrl(string $token): string
    {
        return url('/invite/account/'.$token);
    }

    /**
     * The signed-in admin's school is the only school the limiter may use.
     */
    private function actorForPerson(?User $actor, User $person): ?User
    {
        if (! $actor instanceof User || (int) $actor->school_id === 0) {
            return null;
        }

        if ((int) $actor->school_id !== (int) $person->school_id) {
            return null;
        }

        return $actor;
    }

    private function sendEmail(TeacherInvite $invite, string $token, School $school, string $role): void
    {
        if (\App\Services\Onboarding\ChecklistInviteHold::active()) {
            return;
        }

        try {
            Mail::to($invite->email)->queue(new StudentParentSetupLinkMail(
                name: $invite->name ?? ucfirst($role),
                schoolName: $school->name ?? 'KlassApp',
                inviteUrl: $this->setupUrl($token),
                role: $role,
                expiresAt: $invite->expires_at,
            ));
        } catch (\Throwable) {
            Log::warning('Student or parent setup link: email failed', [
                'invite_id' => $invite->id,
                'school_id' => $invite->school_id,
                'role' => $role,
            ]);
        }
    }

    private function revokeOpenInvites(int $schoolId, string $email, int $userId): void
    {
        TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->where(static function ($query) use ($email, $userId): void {
                $query->whereRaw('lower(email) = ?', [$email])
                    ->orWhere('user_id', $userId);
            })
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
