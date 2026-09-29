<?php

namespace App\Services;

use App\Mail\TeacherInviteMail;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\UserProvisioning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single source of truth for inviting a Class Teacher to take ownership
 * of a specific class (StandardLink + its Section).
 *
 * Supports both "new teacher" (create User + email credentials)
 * and "existing teacher" (reassign CT role + notification email).
 *
 * Every operation is scoped by school_id and respects usergroup_id==5.
 */
class ClassTeacherInviteService
{
    /**
     * @param User         $inviter         Admin or school staff doing the inviting
     * @param StandardLink $standardLink    Stream to assign the CT to
     * @param array        $data
     *                                    Required:
     *                                    - email: string
     *                                    Optional (for new teacher):
     *                                    - name: string
     *                                    - phone: string
     *                                    Optional (for existing teacher):
     *                                    - existing_teacher_id: ?int
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    public static function invite(User $inviter, StandardLink $standardLink, array $data): array
    {
        $inviterSchoolId = (int) $inviter->school_id;

        if ($inviterSchoolId === 0) {
            return self::result(false, 'Inviter is not assigned to a school.');
        }

        // ── Validate StandardLink belongs to inviter's school ──
        if ((int) $standardLink->school_id !== $inviterSchoolId || (int) $standardLink->status !== 1) {
            return self::result(false, 'Class not found or not in your school.');
        }

        $section = $standardLink->section;
        if ($section === null || (int) $section->school_id !== $inviterSchoolId || (int) $section->status !== 1) {
            return self::result(false, 'Class section not found or not in your school.');
        }

        $email = mb_strtolower(trim($data['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::result(false, 'A valid email address is required.');
        }

        $school = School::find($inviterSchoolId);
        $schoolName = $school?->name ?? 'your school';
        $className = $section->name ?? $standardLink->stream ?? 'the class';

        // ── Existing teacher path ─────────────────────────────────
        $existingTeacherId = isset($data['existing_teacher_id']) ? (int) $data['existing_teacher_id'] : 0;

        if ($existingTeacherId > 0) {
            return self::assignExistingTeacher(
                $inviterSchoolId,
                $standardLink,
                $section,
                $existingTeacherId,
                $email,
                $schoolName,
                $className,
            );
        }

        // ── New teacher path ────────────────────────────────────
        $name = trim($data['name'] ?? '');
        if ($name === '' || strlen($name) < 3) {
            return self::result(false, 'Teacher name must be at least 3 characters.');
        }

        return self::createAndAssignNewTeacher(
            $inviterSchoolId,
            $standardLink,
            $section,
            $email,
            $name,
            trim($data['phone'] ?? ''),
            $schoolName,
            $className,
        );
    }

    private static function assignExistingTeacher(
        int $schoolId,
        StandardLink $standardLink,
        Section $section,
        int $existingTeacherId,
        string $email,
        string $schoolName,
        string $className,
    ): array {
        $teacher = User::whereKey($existingTeacherId)
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->first();

        if ($teacher === null) {
            return self::result(false, 'Teacher not found or not in your school.');
        }

        if ($teacher->email !== $email) {
            return self::result(false, 'The email does not match the selected teacher.');
        }

        $alreadyCt = (int) $standardLink->class_teacher_id === $teacher->id
            || (int) $section->class_teacher_id === $teacher->id;

        if ($alreadyCt) {
            return self::result(false, "{$teacher->name} is already the class teacher for {$className}.");
        }

        try {
            DB::beginTransaction();

            $standardLink->class_teacher_id = $teacher->id;
            $standardLink->save();

            $section->class_teacher_id = $teacher->id;
            $section->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CT invite: failed to assign existing teacher', [
                'school_id' => $schoolId,
                'teacher_id' => $teacher->id,
                'standard_link_id' => $standardLink->id,
                'error' => $e->getMessage(),
            ]);

            return self::result(false, 'Could not assign class teacher. Please try again.');
        }

        // Send reassignment notification (no credentials — teacher already has them)
        try {
            Mail::to($teacher->email)->queue(new TeacherInviteMail(
                $teacher->name,
                $teacher->email,
                '',       // no password — existing teacher already has credentials
                $schoolName,
                $className,
            ));
        } catch (\Exception $e) {
            Log::warning('CT invite: reassignment email failed', [
                'email' => $teacher->email,
                'error' => $e->getMessage(),
            ]);
        }

        return self::result(
            true,
            "{$teacher->name} has been assigned as class teacher for {$className}.",
            ['teacher_id' => $teacher->id],
        );
    }

    private static function createAndAssignNewTeacher(
        int $schoolId,
        StandardLink $standardLink,
        Section $section,
        string $email,
        string $name,
        string $phone,
        string $schoolName,
        string $className,
    ): array {
        // Duplicate email check (global + pending invites)
        if (User::where('email', $email)->exists()) {
            return self::result(false, "A user with email **{$email}** already exists.");
        }

        if (TeacherInvite::where('email', $email)
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            return self::result(false, "An invite for **{$email}** is already pending.");
        }

        $school = School::find($schoolId);
        if (! $school) {
            return self::result(false, 'School not found.');
        }

        // Issue a one-time invite link instead of creating the user immediately
        $result = TeacherInviteLinkService::issue(
            school: $school,
            email: $email,
            name: $name,
            standardLink: $standardLink,
            phone: $phone !== '' ? $phone : null,
        );

        $invite = $result['invite'];
        $token = $result['token'];

        // Send invite via email (no password — just the link)
        TeacherInviteLinkService::sendEmail($invite, $token, $school, $className);

        // Also send via WhatsApp when a phone number is available
        if ($phone !== '' && trim($phone) !== '') {
            TeacherInviteLinkService::sendWhatsApp($invite, $token, $school, $className);
        }

        $channels = $phone !== '' && trim($phone) !== '' ? 'email and WhatsApp' : 'email';

        return self::result(
            true,
            "An invite link has been sent to {$name} at {$email} (via {$channels}). They must set their own password to activate the account.",
        );
    }

    /**
     * @param array<string, mixed> $extra
     * @return array{success: bool, message: string} + $extra
     */
    private static function result(bool $success, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra);
    }
}
