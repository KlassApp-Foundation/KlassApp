<?php

namespace App\Services;

use App\Mail\TeacherInviteMail;
use App\Models\Academics\Classes;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Onboarding\Country\SchoolTypePreset;
use App\Services\OnboardingStepsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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
     * Levels offered to a school in Uganda before it has created that class.
     * A school in any other country types its own names instead.
     *
     * @var list<string>
     */
    public const CANONICAL_CLASS_NAMES = [
        'Baby Class', 'Middle Class', 'Top Class',
        'P.1', 'P.2', 'P.3', 'P.4', 'P.5', 'P.6', 'P.7',
        'S.1', 'S.2', 'S.3', 'S.4', 'S.5', 'S.6',
    ];

    /**
     * Rate-limit key prefix for invite tokens that match no row.
     * The rest of the key is the client IP. These attempts do not share a school bucket.
     */
    public const INVALID_ACCEPT_KEY_PREFIX = 'invalid-ip:';

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
            $this->notifyExistingTeacher($actor, $teacher, $className, $replacing, $standardLink);
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
     * Uganda schools are offered the canonical levels. Every other country
     * types its own level names.
     */
    public function offersUgandaLevels(int $schoolId): bool
    {
        $country = School::query()->whereKey($schoolId)->value('registration_country');

        return OnboardingStepsService::isUganda(is_string($country) ? $country : null);
    }

    /**
     * Class names this school may add, in level order (P.1, P.2, … P.10),
     * not alphabetical order. A Uganda school sees the canonical levels, its
     * type preset, and any name it already uses. Another country sees only
     * the names it already uses.
     *
     * @return list<string>
     */
    public function namesForSchool(int $schoolId): array
    {
        $existing = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->all();

        $canonical = [];
        $preset = [];
        $school = School::query()->whereKey($schoolId)->first();
        if ($school instanceof School && OnboardingStepsService::isUganda($school->registration_country)) {
            $canonical = self::CANONICAL_CLASS_NAMES;
            $preset = SchoolTypePreset::forSchool($school)?->classNames() ?? [];
        }

        return $this->sortByLevel($this->uniqueNames(array_merge(
            $canonical,
            $preset,
            $existing,
        )));
    }

    /**
     * Save a class. A Uganda school must pick a name namesForSchool() offers.
     * Any other country saves the name the school typed.
     */
    public function storeNamedClass(User $actor, int $standardId, string $name): Classes
    {
        $schoolId = (int) $actor->school_id;
        $name = trim($name);
        $allowed = $this->namesForSchool($schoolId);
        $uganda = $this->offersUgandaLevels($schoolId);

        if ($name === '' || ($uganda && ! in_array($name, $allowed, true))) {
            throw ValidationException::withMessages([
                'name' => $uganda
                    ? 'Choose a class name from the list for this school.'
                    : 'Enter a class name.',
            ]);
        }

        $standard = Standard::query()
            ->where('school_id', $schoolId)
            ->whereKey($standardId)
            ->first();

        if (! $standard instanceof Standard) {
            throw ValidationException::withMessages([
                'standard_id' => 'Choose a level from this school.',
            ]);
        }

        $position = array_search($name, $allowed, true);

        return Classes::query()->create([
            'name' => $name,
            'standard_id' => $standard->id,
            'school_id' => $schoolId,
            'status' => '1',
            'position' => $position === false ? count($allowed) + 1 : $position + 1,
        ]);
    }

    /**
     * Stream-form class teacher. A blank id clears the stream column on edit.
     * Any other id must be a teacher in this school, and the write goes through commit.
     */
    public function applyStreamTeacher(User|null $actor, StandardLink $standardLink, mixed $teacherId, bool $clearWhenBlank): void
    {
        if ($this->blankTeacherId($teacherId)) {
            if ($clearWhenBlank) {
                $this->clearStreamTeacher($actor, $standardLink);
            }

            return;
        }

        $teacher = $this->teacherInSchool($actor, (int) $standardLink->school_id, (int) $teacherId);
        if (! $actor instanceof User || ! $teacher instanceof User) {
            throw ValidationException::withMessages([
                'class_teacher_id' => 'Choose a teacher from this school.',
            ]);
        }

        $currentId = $standardLink->class_teacher_id !== null
            ? (int) $standardLink->class_teacher_id
            : null;
        if ($currentId !== null && $currentId === (int) $teacher->id) {
            return;
        }

        $placed = $this->commit($actor, $standardLink, $teacher, true, false, 'stream_form');
        if (! ($placed['success'] ?? false)) {
            throw ValidationException::withMessages([
                'class_teacher_id' => $placed['message'] ?? 'Could not assign the class teacher.',
            ]);
        }
    }

    /**
     * Section class teacher from the stream form. Null clears it.
     * A change revokes pending invites for the class and writes one log.
     * The same teacher again does neither.
     */
    public function applySectionTeacher(User|null $actor, Section $section, mixed $teacherId): void
    {
        if ($this->blankTeacherId($teacherId)) {
            $this->clearSectionTeacher($actor, $section);

            return;
        }

        $teacher = $this->teacherInSchool($actor, (int) $section->school_id, (int) $teacherId);
        if (! $actor instanceof User || ! $teacher instanceof User) {
            throw ValidationException::withMessages([
                'section_class_teacher_id' => 'Choose a teacher from this school.',
            ]);
        }

        if ((int) $section->class_teacher_id === (int) $teacher->id) {
            return;
        }

        $previous = $section->class_teacher_id !== null ? (int) $section->class_teacher_id : null;
        $section->class_teacher_id = $teacher->id;
        $section->save();
        $this->recordColumnChange($actor, $section, $teacher, 'section_form', $previous);
    }

    /**
     * Clear the stream teacher and the section teacher together.
     * Already-empty columns do not revoke an invite or write a log.
     */
    public function clearStreamTeacher(User|null $actor, StandardLink $standardLink): void
    {
        $section = $standardLink->section;
        $streamId = $standardLink->class_teacher_id !== null ? (int) $standardLink->class_teacher_id : null;
        $sectionId = $section instanceof Section && $section->class_teacher_id !== null
            ? (int) $section->class_teacher_id
            : null;

        if ($streamId === null && $sectionId === null) {
            return;
        }

        $actor = $this->actorForSchool($actor, (int) $standardLink->school_id, 'class_teacher_id');

        $standardLink->class_teacher_id = null;
        $standardLink->save();

        if ($section instanceof Section && (int) $section->school_id === (int) $standardLink->school_id) {
            $section->class_teacher_id = null;
            $section->save();
        }

        $this->recordColumnChange($actor, $section instanceof Section ? $section : null, null, 'clear', $streamId ?? $sectionId, $standardLink);
    }

    private function clearSectionTeacher(User|null $actor, Section $section): void
    {
        if ($section->class_teacher_id === null) {
            return;
        }

        $actor = $this->actorForSchool($actor, (int) $section->school_id, 'section_class_teacher_id');
        $previous = (int) $section->class_teacher_id;
        $section->class_teacher_id = null;
        $section->save();
        $this->recordColumnChange($actor, $section, null, 'clear', $previous);
    }

    /**
     * Invite a teacher who is not tied to a class yet. No user row and no
     * password are stored. The same invite service issues the link.
     *
     * @param  array{email?: string, name?: string, phone?: string}  $data
     * @return array{success: bool, message: string}
     */
    public function inviteWithoutClass(User $actor, array $data): array
    {
        $schoolId = (int) $actor->school_id;
        if ($schoolId === 0) {
            return $this->result(false, 'Inviter is not assigned to a school.');
        }

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->result(false, 'A valid email address is required.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || strlen($name) < 3) {
            return $this->result(false, 'Teacher name must be at least 3 characters.');
        }

        $phone = trim((string) ($data['phone'] ?? ''));

        if ($this->issuingIsLimited($actor, hash('sha256', $email))) {
            return $this->result(false, 'Too many class teacher changes. Wait an hour and try again.');
        }

        if ($this->emailIsTakenForSchool($schoolId, $email)) {
            return $this->result(false, "A user with email **{$email}** already exists.");
        }

        if (TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            return $this->result(false, "An invite for **{$email}** is already pending.");
        }

        $school = School::query()->whereKey($schoolId)->first();
        if (! $school instanceof School) {
            return $this->result(false, 'School not found.');
        }

        $issued = TeacherInviteLinkService::issue(
            school: $school,
            email: $email,
            name: $name,
            phone: $phone !== '' ? $phone : null,
        );

        TeacherInviteLinkService::sendEmail($issued['invite'], $issued['token'], $school, null);

        $whatsapp = $phone !== '' && (bool) config('invites.staff_whatsapp_enabled');
        if ($whatsapp) {
            TeacherInviteLinkService::sendWhatsApp($issued['invite'], $issued['token'], $school, null);
        }

        $channel = $whatsapp ? 'email and WhatsApp' : 'email';

        return $this->result(
            true,
            "An invite link has been sent to {$name} at {$email} (via {$channel}). They must set their own password to activate the account.",
        );
    }

    /**
     * Named limiter teacher-invite-issue. The school is the actor's school.
     * A school_id in the request body is ignored.
     */
    public function issuingIsLimited(User $actor, string $inviteKey): bool
    {
        $request = Request::create('/', 'POST', [
            'school_id' => 0,
            'invite_key' => $inviteKey,
        ]);
        $request->setUserResolver(static fn () => $actor);

        return $this->issueRequestIsLimited($request);
    }

    /**
     * True when this request is over the issue limit. The bucket is the
     * authenticated admin's school, never school_id from the body.
     */
    public function issueRequestIsLimited(Request $request): bool
    {
        return $this->consumeNamedLimiter('teacher-invite-issue', $request);
    }

    public static function limiterCap(string $name): int
    {
        if (app()->runningUnitTests() && ! config('invites.force_rate_limits')) {
            return 100000;
        }

        return max(1, (int) config('invites.'.$name, 30));
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

        if ($this->issuingIsLimited($actor, 'existing:'.$teacher->id)) {
            return $this->result(false, 'Too many class teacher changes. Wait an hour and try again.');
        }

        $previous = $standardLink->class_teacher_id ?? $section->class_teacher_id;
        $action = $previous !== null ? 'replace' : 'assign';

        return $this->commit($actor, $standardLink, $teacher, true, true, $action);
    }

    /**
     * Wizard class-teacher upload. A teacher created on this row was already
     * invited. An existing teacher is notified here: a setup link when they
     * have never signed in, and the class notice when they have.
     *
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    public function assignFromUpload(User $actor, StandardLink $standardLink, User $teacher, bool $justCreated): array
    {
        return $this->commit($actor, $standardLink, $teacher, true, ! $justCreated, 'upload');
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
        if ($this->issuingIsLimited($actor, hash('sha256', $email))) {
            return $this->result(false, 'Too many class teacher changes. Wait an hour and try again.');
        }

        if ($this->emailIsTakenForSchool((int) $actor->school_id, $email)) {
            return $this->result(false, "A user with email **{$email}** already exists.");
        }

        if (TeacherInvite::query()
            ->where('school_id', (int) $actor->school_id)
            ->where('email', $email)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            return $this->result(false, "An invite for **{$email}** is already pending.");
        }

        $school = School::query()->whereKey((int) $actor->school_id)->first();
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

    private function notifyExistingTeacher(User $actor, User $teacher, string $className, bool $replacing, ?StandardLink $standardLink = null): void
    {
        $invites = app(TeacherAccountInviteService::class);
        if (! $invites->hasSignedIn($teacher)) {
            if ($invites->emailIsUsable($teacher->email)) {
                // assign() and assignFromUpload already consumed the issuing limiter.
                $invites->inviteIfNeeded($teacher, $actor, $className, $standardLink, false);
            }

            return;
        }

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

    private function actorForSchool(?User $actor, int $schoolId, string $field): User
    {
        if (! $actor instanceof User || $schoolId === 0 || (int) $actor->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                $field => 'Choose a teacher from this school.',
            ]);
        }

        return $actor;
    }

    /**
     * A direct column write still revokes pending invites and writes one log.
     * No active stream means there is nothing to revoke and nowhere to attach the log.
     */
    private function recordColumnChange(
        User $actor,
        ?Section $section,
        ?User $teacher,
        string $action,
        ?int $previousTeacherId,
        ?StandardLink $standardLink = null,
    ): void {
        if (! $standardLink instanceof StandardLink && $section instanceof Section) {
            $standardLink = StandardLink::query()
                ->where('school_id', $section->school_id)
                ->where('section_id', $section->id)
                ->orderByDesc('id')
                ->first();
        }

        if (! $standardLink instanceof StandardLink) {
            return;
        }

        $sectionId = $section instanceof Section
            ? (int) $section->id
            : (int) $standardLink->section_id;

        $this->revokePendingForSection((int) $standardLink->school_id, $sectionId, null);
        $this->logAssignment($actor, $standardLink, $teacher, $action, $previousTeacherId);
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
            'section_form' => "Saved the section teacher for {$className}.",
            'clear' => "Cleared the class teacher for {$className}.",
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

    private function blankTeacherId(mixed $teacherId): bool
    {
        return $teacherId === null || $teacherId === '' || (int) $teacherId === 0;
    }

    /**
     * The school lookup runs even when nobody is signed in. A missing actor
     * cannot skip it and still accept the teacher id.
     */
    private function teacherInSchool(?User $actor, int $schoolId, int $teacherId): ?User
    {
        if ($schoolId <= 0 || $teacherId <= 0) {
            return null;
        }

        $teacher = User::query()
            ->where('school_id', $schoolId)
            ->whereKey($teacherId)
            ->where('usergroup_id', 5)
            ->first();

        if (! $teacher instanceof User) {
            return null;
        }

        if (! $actor instanceof User || (int) $actor->school_id !== $schoolId) {
            return null;
        }

        return $teacher;
    }

    /**
     * True when this school already has the email, or the email is on a user
     * with no school. An email that belongs only to another school is not a match.
     */
    private function emailIsTakenForSchool(int $schoolId, string $email): bool
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->where(function ($query) use ($schoolId): void {
                $query->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->exists();
    }

    /**
     * Hit the named limiter the same way the throttle middleware does:
     * the cache key is md5(name + limit key). Returns true when limited.
     */
    private function consumeNamedLimiter(string $name, Request $request): bool
    {
        $callback = RateLimiter::limiter($name);
        if ($callback === null) {
            return false;
        }

        $limits = $callback($request);
        if (! is_array($limits)) {
            $limits = [$limits];
        }

        $hits = [];
        foreach ($limits as $limit) {
            $key = md5($name.$limit->key);
            if (RateLimiter::tooManyAttempts($key, $limit->maxAttempts)) {
                return true;
            }
            $hits[] = [$key, $limit->decaySeconds];
        }

        foreach ($hits as [$key, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return false;
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function uniqueNames(array $names): array
    {
        $seen = [];
        $clean = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $key = strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = $name;
        }

        return $clean;
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function sortByLevel(array $names): array
    {
        usort($names, function (string $left, string $right): int {
            $leftRank = $this->levelRank($left);
            $rightRank = $this->levelRank($right);

            return $leftRank <=> $rightRank ?: strcasecmp($left, $right);
        });

        return array_values($names);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function levelRank(string $name): array
    {
        $key = strtolower(trim($name));
        $nursery = ['baby class' => 1, 'middle class' => 2, 'top class' => 3];
        if (isset($nursery[$key])) {
            return [0, $nursery[$key]];
        }

        if (preg_match('/^p\.(\d+)$/', $key, $match)) {
            return [1, (int) $match[1]];
        }

        $primary = [
            'primary one' => 1, 'primary two' => 2, 'primary three' => 3, 'primary four' => 4,
            'primary five' => 5, 'primary six' => 6, 'primary seven' => 7, 'primary eight' => 8,
            'primary nine' => 9, 'primary ten' => 10,
        ];
        if (isset($primary[$key])) {
            return [1, $primary[$key]];
        }

        if (preg_match('/^s\.(\d+)$/', $key, $match)) {
            return [2, (int) $match[1]];
        }

        $senior = [
            'senior one' => 1, 'senior two' => 2, 'senior three' => 3,
            'senior four' => 4, 'senior five' => 5, 'senior six' => 6,
        ];
        if (isset($senior[$key])) {
            return [2, $senior[$key]];
        }

        return [9, 0];
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
