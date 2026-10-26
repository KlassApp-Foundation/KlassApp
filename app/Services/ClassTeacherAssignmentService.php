<?php

namespace App\Services;

use App\Mail\TeacherInviteMail;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Onboarding\Country\SchoolTypePreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One assignment path for a class teacher.
 *
 * The admin class page, the invite form, the wizard, and Toshi all call
 * this service. It picks an existing teacher, invites a new one, or
 * replaces the current one. A replacement revokes pending invites for
 * that class. Email is the channel. WhatsApp stays off until the
 * staff_invite template is approved.
 */
class ClassTeacherAssignmentService
{
    /**
     * @param  array{
     *     email?: string,
     *     name?: string,
     *     phone?: string,
     *     existing_teacher_id?: int|string|null,
     *     confirmed?: bool|int|string|null
     * }  $data
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    public function assign(User $actor, StandardLink $standardLink, array $data): array
    {
        if (! $this->isConfirmed($data['confirmed'] ?? null)) {
            return $this->result(false, 'Confirm this assignment before it is saved.');
        }

        $schoolId = (int) $actor->school_id;
        if ($schoolId === 0) {
            return $this->result(false, 'Inviter is not assigned to a school.');
        }

        if ((int) $standardLink->school_id !== $schoolId || (int) $standardLink->status !== 1) {
            return $this->result(false, 'Class not found or not in your school.');
        }

        $section = $standardLink->section;
        if ($section === null || (int) $section->school_id !== $schoolId || (int) $section->status !== 1) {
            return $this->result(false, 'Class section not found or not in your school.');
        }

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->result(false, 'A valid email address is required.');
        }

        $existingTeacherId = isset($data['existing_teacher_id']) ? (int) $data['existing_teacher_id'] : 0;
        if ($existingTeacherId > 0) {
            return $this->assignExisting($actor, $standardLink, $section, $existingTeacherId, $email);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || strlen($name) < 3) {
            return $this->result(false, 'Teacher name must be at least 3 characters.');
        }

        return $this->inviteNew(
            $actor,
            $standardLink,
            $section,
            $email,
            $name,
            trim((string) ($data['phone'] ?? '')),
        );
    }

    /**
     * The only write of both class-teacher columns.
     *
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    public function commit(
        User $actor,
        StandardLink $standardLink,
        User $teacher,
        bool $confirmed,
        bool $notify,
        string $action,
        ?int $exceptInviteId = null,
    ): array {
        if (! $confirmed) {
            return $this->result(false, 'Confirm this assignment before it is saved.');
        }

        $schoolId = (int) $actor->school_id;
        if ($schoolId === 0 || (int) $teacher->school_id !== $schoolId || (int) $teacher->usergroup_id !== 5) {
            return $this->result(false, 'Teacher not found or not in your school.');
        }

        if ((int) $standardLink->school_id !== $schoolId) {
            return $this->result(false, 'Class not found or not in your school.');
        }

        $section = $standardLink->section;
        if ($section === null || (int) $section->school_id !== $schoolId) {
            return $this->result(false, 'Class section not found or not in your school.');
        }

        $previousTeacherId = $standardLink->class_teacher_id !== null
            ? (int) $standardLink->class_teacher_id
            : ($section->class_teacher_id !== null ? (int) $section->class_teacher_id : null);
        $replacing = $previousTeacherId !== null && $previousTeacherId !== (int) $teacher->id;

        try {
            DB::transaction(function () use ($standardLink, $section, $teacher, $schoolId, $exceptInviteId): void {
                $standardLink->class_teacher_id = $teacher->id;
                $standardLink->save();

                $section->class_teacher_id = $teacher->id;
                $section->save();

                $this->revokePendingForSection($schoolId, (int) $section->id, $exceptInviteId);
            });
        } catch (\Throwable $e) {
            Log::error('Class teacher assignment failed', [
                'school_id' => $schoolId,
                'teacher_id' => $teacher->id,
                'standard_link_id' => $standardLink->id,
                'error' => $e->getMessage(),
            ]);

            return $this->result(false, 'Could not assign class teacher. Please try again.');
        }

        $className = $this->className($section, $standardLink);
        $this->logAssignment($actor, $standardLink, $teacher, $action, $previousTeacherId);

        if ($notify) {
            $this->notifyExistingTeacher($actor, $teacher, $className, $replacing);
        }

        $verb = $replacing ? 'replaced the class teacher' : 'assigned as class teacher';

        return $this->result(
            true,
            $replacing
                ? "{$teacher->name} has replaced the class teacher for {$className}."
                : "{$teacher->name} has been assigned as class teacher for {$className}.",
            ['teacher_id' => $teacher->id, 'verb' => $verb],
        );
    }

    /**
     * Class names this school already uses. A school with no classes yet
     * gets the names from its own type preset, not a second fixed list.
     *
     * @return list<string>
     */
    public function namesForSchool(int $schoolId): array
    {
        $existing = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values()
            ->all();

        if ($existing !== []) {
            return $existing;
        }

        $school = School::query()->find($schoolId);
        if (! $school instanceof School) {
            return [];
        }

        return SchoolTypePreset::forSchool($school)?->classNames() ?? [];
    }

    public function tooManyCreates(User $actor): bool
    {
        return $this->tooMany('teacher-invite-create:user:'.$actor->id, $this->attempts('create_attempts'));
    }

    public function tooManyAccepts(?string $ip): bool
    {
        $key = 'teacher-invite-accept:'.hash('sha256', (string) $ip);

        return $this->tooMany($key, $this->attempts('accept_attempts'));
    }

    /**
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    private function assignExisting(
        User $actor,
        StandardLink $standardLink,
        Section $section,
        int $existingTeacherId,
        string $email,
    ): array {
        $teacher = User::query()
            ->whereKey($existingTeacherId)
            ->where('school_id', (int) $actor->school_id)
            ->where('usergroup_id', 5)
            ->first();

        if ($teacher === null) {
            return $this->result(false, 'Teacher not found or not in your school.');
        }

        if ($teacher->email !== $email) {
            return $this->result(false, 'The email does not match the selected teacher.');
        }

        $already = (int) $standardLink->class_teacher_id === (int) $teacher->id
            || (int) $section->class_teacher_id === (int) $teacher->id;
        if ($already) {
            $className = $this->className($section, $standardLink);

            return $this->result(false, "{$teacher->name} is already the class teacher for {$className}.");
        }

        if ($this->tooManyCreates($actor)) {
            return $this->result(false, 'Too many class teacher changes. Wait an hour and try again.');
        }

        $previous = $standardLink->class_teacher_id ?? $section->class_teacher_id;
        $action = $previous !== null ? 'replace' : 'assign';

        return $this->commit($actor, $standardLink, $teacher, true, true, $action);
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function inviteNew(
        User $actor,
        StandardLink $standardLink,
        Section $section,
        string $email,
        string $name,
        string $phone,
    ): array {
        if (User::query()->where('email', $email)->exists()) {
            return $this->result(false, "A user with email **{$email}** already exists.");
        }

        if (TeacherInvite::query()
            ->where('email', $email)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            return $this->result(false, "An invite for **{$email}** is already pending.");
        }

        if ($this->tooManyCreates($actor)) {
            return $this->result(false, 'Too many class teacher changes. Wait an hour and try again.');
        }

        $school = School::query()->find((int) $actor->school_id);
        if (! $school instanceof School) {
            return $this->result(false, 'School not found.');
        }

        $className = $this->className($section, $standardLink);
        $replacing = $standardLink->class_teacher_id !== null || $section->class_teacher_id !== null;

        $this->revokePendingForSection((int) $school->id, (int) $section->id, null);

        $issued = TeacherInviteLinkService::issue(
            school: $school,
            email: $email,
            name: $name,
            standardLink: $standardLink,
            phone: $phone !== '' ? $phone : null,
        );

        TeacherInviteLinkService::sendEmail($issued['invite'], $issued['token'], $school, $className);

        $whatsapp = $phone !== '' && (bool) config('invites.staff_whatsapp_enabled');
        if ($whatsapp) {
            TeacherInviteLinkService::sendWhatsApp($issued['invite'], $issued['token'], $school, $className);
        }

        $this->logAssignment($actor, $standardLink, null, $replacing ? 'invite_replace' : 'invite', $standardLink->class_teacher_id !== null ? (int) $standardLink->class_teacher_id : null, $issued['invite']->id);

        $channel = $whatsapp ? 'email and WhatsApp' : 'email';

        return $this->result(
            true,
            "An invite link has been sent to {$name} at {$email} (via {$channel}). They must set their own password to activate the account.",
        );
    }

    public function revokePendingForSection(int $schoolId, int $sectionId, ?int $exceptInviteId): void
    {
        $linkIds = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->pluck('id');

        if ($linkIds->isEmpty()) {
            return;
        }

        $query = TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->whereIn('standard_link_id', $linkIds)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at');

        if ($exceptInviteId !== null) {
            $query->where('id', '!=', $exceptInviteId);
        }

        $query->update(['revoked_at' => now()]);
    }

    private function notifyExistingTeacher(User $actor, User $teacher, string $className, bool $replacing): void
    {
        $schoolName = School::query()->whereKey((int) $actor->school_id)->value('name') ?? 'your school';

        try {
            Mail::to($teacher->email)->queue(new TeacherInviteMail(
                $teacher->name,
                $teacher->email,
                (string) $schoolName,
                $className,
                existingAccount: true,
                assignedBy: $actor->name ?: 'Your school admin',
                replacing: $replacing,
            ));
        } catch (\Exception $e) {
            Log::warning('Class teacher notice failed', [
                'school_id' => $actor->school_id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logAssignment(
        User $actor,
        StandardLink $standardLink,
        ?User $teacher,
        string $action,
        ?int $previousTeacherId,
        ?int $inviteId = null,
    ): void {
        $className = $this->className($standardLink->section, $standardLink);
        $description = match ($action) {
            'replace' => "Replaced the class teacher for {$className}.",
            'invite', 'invite_replace' => "Invited a class teacher for {$className}.",
            'upload' => "Assigned a class teacher for {$className} from the teacher file.",
            'toshi' => "Assigned a class teacher for {$className} from Toshi.",
            'claim' => "A teacher claimed the class teacher invite for {$className}.",
            'stream_form' => "Saved the class teacher for {$className} on the stream form.",
            default => "Assigned a class teacher for {$className}.",
        };

        activity()
            ->performedOn($standardLink)
            ->causedBy($actor)
            ->withProperties([
                'school_id' => (int) $standardLink->school_id,
                'section_id' => (int) $standardLink->section_id,
                'teacher_id' => $teacher?->id,
                'previous_teacher_id' => $previousTeacherId,
                'action' => $action,
                'invite_id' => $inviteId,
            ])
            ->useLog('class_teacher')
            ->log($description);
    }

    private function className(?Section $section, StandardLink $standardLink): string
    {
        $name = trim((string) ($section?->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $stream = trim((string) ($standardLink->stream ?? ''));

        return $stream !== '' ? $stream : 'the class';
    }

    private function isConfirmed(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    private function tooMany(string $key, int $max): bool
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return true;
        }

        RateLimiter::hit($key, (int) config('invites.rate_decay_seconds', 3600));

        return false;
    }

    private function attempts(string $name): int
    {
        if (app()->runningUnitTests() && ! config('invites.force_rate_limits')) {
            return 100000;
        }

        return max(1, (int) config('invites.'.$name, 30));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{success: bool, message: string}
     */
    private function result(bool $success, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra);
    }
}
