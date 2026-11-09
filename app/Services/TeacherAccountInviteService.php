<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Invites a teacher the school has already created.
 *
 * The link itself is issued by TeacherInviteLinkService. This service only
 * decides when to send one, when a teacher has no usable email, and how a
 * teacher who already has a row finishes setup. It does not mint tokens.
 */
class TeacherAccountInviteService
{
    public const NEEDS_EMAIL = 'This teacher has no email yet. Add one before they can be invited.';

    public function emailIsUsable(?string $email): bool
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $at = strrpos($email, '@');
        $host = $at === false ? '' : substr($email, $at + 1);
        if ($host === '' || $host === 'local' || str_ends_with($host, '.local') || $host === 'school.edu') {
            return false;
        }

        return preg_match('/^teacher\.[a-z0-9]+@.+\.test$/', $email) !== 1;
    }

    public function hasSignedIn(User $teacher): bool
    {
        return ActivityLog::query()
            ->where('school_id', (int) $teacher->school_id)
            ->where('log_name', 'login')
            ->where('causer_id', (int) $teacher->id)
            ->exists();
    }

    public function canResend(User $teacher): bool
    {
        return (int) $teacher->usergroup_id === 5
            && $teacher->status === 'active'
            && $this->emailIsUsable($teacher->email)
            && ! $this->hasSignedIn($teacher);
    }

    /**
     * Send a setup link when this teacher has a real email and has never signed in.
     * A placeholder address is stored on the row and is not invited.
     *
     * @return array{invited: bool, reason: string}
     */
    public function inviteIfNeeded(
        User $teacher,
        ?User $actor,
        ?string $className = null,
        ?StandardLink $standardLink = null,
        bool $consumeLimit = true,
        string $context = TeacherInviteLinkService::CONTEXT_INTERACTIVE,
    ): array {
        if ($teacher->status === null || $teacher->usergroup_id === null) {
            $teacher->refresh();
        }

        if ((int) $teacher->usergroup_id !== 5 || $teacher->status !== 'active') {
            return ['invited' => false, 'reason' => 'not_teacher'];
        }

        if ($this->hasSignedIn($teacher)) {
            return ['invited' => false, 'reason' => 'signed_in'];
        }

        if (! $this->emailIsUsable($teacher->email)) {
            return ['invited' => false, 'reason' => 'no_email'];
        }

        $actor = $this->actorForTeacher($actor, $teacher);
        if ($consumeLimit) {
            $limited = TeacherInviteLinkService::issueIsLimited(
                $actor,
                hash('sha256', mb_strtolower((string) $teacher->email).'|'.$teacher->id),
                $context,
            );
            if ($limited) {
                return ['invited' => false, 'reason' => 'limited'];
            }
        }

        $school = School::query()->whereKey((int) $teacher->school_id)->first();
        if (! $school instanceof School) {
            return ['invited' => false, 'reason' => 'no_school'];
        }

        $this->revokeOpenInvites((int) $school->id, (string) $teacher->email);

        $issued = TeacherInviteLinkService::issue(
            school: $school,
            email: (string) $teacher->email,
            name: trim((string) $teacher->name) !== '' ? trim((string) $teacher->name) : 'Teacher',
            standardLink: $standardLink,
            phone: filled($teacher->mobile_no) ? (string) $teacher->mobile_no : null,
        );

        $issued['invite']->user_id = $teacher->id;
        if (! $standardLink instanceof StandardLink) {
            $issued['invite']->role = 'teacher';
        }
        $issued['invite']->save();

        TeacherInviteLinkService::sendEmail($issued['invite'], $issued['token'], $school, $className);
        TeacherInviteLinkService::sendWhatsApp($issued['invite'], $issued['token'], $school, $className);

        return ['invited' => true, 'reason' => 'sent'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function resend(User $actor, User $teacher): array
    {
        if ((int) $actor->school_id === 0 || (int) $actor->school_id !== (int) $teacher->school_id) {
            return ['ok' => false, 'message' => 'Teacher not found or not in your school.'];
        }

        if ($this->hasSignedIn($teacher)) {
            return ['ok' => false, 'message' => 'This teacher has already signed in.'];
        }

        if (! $this->emailIsUsable($teacher->email)) {
            return ['ok' => false, 'message' => self::NEEDS_EMAIL];
        }

        $result = $this->inviteIfNeeded($teacher, $actor);
        if ($result['reason'] === 'limited') {
            return ['ok' => false, 'message' => 'Too many setup links. Wait an hour and try again.'];
        }

        if (! ($result['invited'] ?? false)) {
            return ['ok' => false, 'message' => 'Could not send a setup link.'];
        }

        return ['ok' => true, 'message' => 'A new setup link was sent. The previous link no longer works.'];
    }

    /**
     * Finish setup for a teacher row that already exists and has never signed in.
     * Every other invite is claimed by TeacherInviteLinkService, including an
     * email that already belongs to an account the teacher can sign into.
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public function completeClaim(TeacherInvite $invite, string $password): array
    {
        $teacher = $this->teacherWhoCanFinishSetup($invite);
        if (! $teacher instanceof User) {
            return TeacherInviteLinkService::claim($invite, $password);
        }

        if ($invite->isClaimed() || $invite->isRevoked() || $invite->isExpired()) {
            return TeacherInviteLinkService::claim($invite, $password);
        }

        try {
            DB::transaction(function () use ($teacher, $invite, $password): void {
                $teacher->password = Hash::make($password);
                $teacher->is_reset = 0;
                $teacher->email_verified = 1;
                $teacher->status = 'active';
                $teacher->save();

                $invite->user_id = $teacher->id;
                $invite->claimed_at = now();
                $invite->save();

                $this->revokeOpenInvites((int) $invite->school_id, (string) $invite->email, (int) $invite->id);

                if ($invite->standard_link_id) {
                    $link = StandardLink::query()
                        ->where('school_id', $invite->school_id)
                        ->whereKey($invite->standard_link_id)
                        ->first();
                    if ($link instanceof StandardLink && (int) $link->class_teacher_id !== (int) $teacher->id) {
                        app(ClassTeacherAssignmentService::class)->commit(
                            $teacher,
                            $link,
                            $teacher,
                            true,
                            false,
                            'claim',
                            (int) $invite->id,
                        );
                    }
                }
            });
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Could not create your account. Please try again.'];
        }

        return [
            'success' => true,
            'message' => 'Account created. You can now log in.',
            'user' => $teacher->fresh(),
        ];
    }

    /**
     * The signed-in admin's school is the only school the issuing limiter may use.
     */
    private function actorForTeacher(?User $actor, User $teacher): ?User
    {
        if (! $actor instanceof User || (int) $actor->school_id === 0) {
            return null;
        }

        if ((int) $actor->school_id !== (int) $teacher->school_id) {
            return null;
        }

        return $actor;
    }

    private function teacherWhoCanFinishSetup(TeacherInvite $invite): ?User
    {
        $email = mb_strtolower(trim((string) $invite->email));
        if ($email === '') {
            return null;
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        if (! $user instanceof User) {
            return null;
        }

        if ((int) $user->school_id !== (int) $invite->school_id) {
            return null;
        }

        if ((int) $user->usergroup_id !== 5 || $user->status !== 'active') {
            return null;
        }

        if ($this->hasSignedIn($user)) {
            return null;
        }

        return $user;
    }

    private function revokeOpenInvites(int $schoolId, string $email, ?int $exceptId = null): void
    {
        $query = TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
            ->whereNull('claimed_at')
            ->whereNull('revoked_at');

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update(['revoked_at' => now()]);
    }
}
