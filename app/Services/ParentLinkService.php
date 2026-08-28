<?php

namespace App\Services;

use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Unified parent ↔ student linking for WhatsApp (KLS / name / School Pay code paths).
 *
 * Cross-school: one phone → one parent user (school_id NULL) → many student_parent_links.
 */
class ParentLinkService
{
    public function linkByStudentId(string $phone, int $studentId, string $senderName = 'Parent'): ParentLinkResult
    {
        $student = User::with(['studentAcademicLatest.standardLink.standard', 'school'])
            ->find($studentId);

        if (! $student) {
            return ParentLinkResult::notLinked('student_not_found');
        }

        $schoolId = (int) $student->school_id;
        $meta = $this->studentDisplayMeta($student);

        $existingLink = StudentParentLink::query()
            ->where('student_id', $studentId)
            ->where('school_id', $schoolId)
            ->first();

        if ($existingLink) {
            return $this->attachWhatsAppToExistingParentLink(
                $phone,
                (int) $existingLink->parent_id,
                $schoolId,
                $student,
                $meta,
            );
        }

        $whatsappUser = WhatsAppUser::where('phone', $phone)->first();

        if ($whatsappUser?->user_id) {
            $parent = User::find($whatsappUser->user_id);
            if (! $parent || (int) $parent->usergroup_id !== 7) {
                return ParentLinkResult::notLinked('phone_conflict');
            }

            if ($this->linkExists($parent->id, $studentId)) {
                $this->syncWhatsAppUser($whatsappUser, $parent->id, $schoolId);

                return new ParentLinkResult(
                    linked: true,
                    outcome: 'already_linked',
                    parent: $parent,
                    student: $student,
                    studentName: $meta['name'],
                    className: $meta['class'],
                    schoolName: $meta['school'],
                    alreadyLinkedToThisParent: true,
                );
            }

            $this->createLink($parent->id, $studentId, $schoolId);
            $this->syncWhatsAppUser($whatsappUser, $parent->id, $schoolId);

            return new ParentLinkResult(
                linked: true,
                outcome: 'linked_additional_child',
                parent: $parent,
                student: $student,
                studentName: $meta['name'],
                className: $meta['class'],
                schoolName: $meta['school'],
            );
        }

        return DB::transaction(function () use ($phone, $studentId, $schoolId, $senderName, $student, $meta): ParentLinkResult {
            $parent = $this->createParentUser($senderName, $studentId);
            $this->createLink($parent->id, $studentId, $schoolId);
            $this->syncWhatsAppUser(null, $parent->id, $schoolId, $phone);

            return new ParentLinkResult(
                linked: true,
                outcome: 'linked_new_parent',
                parent: $parent,
                student: $student,
                studentName: $meta['name'],
                className: $meta['class'],
                schoolName: $meta['school'],
                isNewParent: true,
            );
        });
    }

    public function linkByPaymentCodeForExistingUser(WhatsAppUser $whatsappUser, string $code): ParentLinkResult
    {
        $studentAcademic = StudentAcademic::where('std_school_pay_number', $code)
            ->whereNull('deleted_at')
            ->first();

        if (! $studentAcademic || ! $studentAcademic->user_id) {
            return ParentLinkResult::notLinked('code_not_found');
        }

        $studentId = (int) $studentAcademic->user_id;
        $schoolId = (int) $studentAcademic->school_id;
        $parentId = (int) $whatsappUser->user_id;

        if ($this->linkExists($parentId, $studentId)) {
            WhatsAppUser::where('id', $whatsappUser->id)->update([
                'student_payment_code' => $code,
                'verified_via_schoolpay' => true,
                'verified_at' => now(),
            ]);

            $student = User::find($studentId);

            return new ParentLinkResult(
                linked: true,
                outcome: 'already_linked',
                parent: User::find($parentId),
                student: $student,
                studentName: $student?->name,
                alreadyLinkedToThisParent: true,
            );
        }

        $this->createLink($parentId, $studentId, $schoolId);

        WhatsAppUser::where('id', $whatsappUser->id)->update([
            'student_payment_code' => $code,
            'verified_via_schoolpay' => true,
            'verified_at' => now(),
            'school_id' => $schoolId,
        ]);

        $student = User::with(['studentAcademicLatest.standardLink'])->find($studentId);
        $meta = $student ? $this->studentDisplayMeta($student) : ['name' => null, 'class' => null, 'school' => null];

        return new ParentLinkResult(
            linked: true,
            outcome: 'linked_additional_child',
            parent: User::find($parentId),
            student: $student,
            studentName: $meta['name'],
            className: $meta['class'],
        );
    }

    private function attachWhatsAppToExistingParentLink(
        string $phone,
        int $parentId,
        int $schoolId,
        User $student,
        array $meta,
    ): ParentLinkResult {
        $whatsappUser = WhatsAppUser::where('phone', $phone)->first();

        if ($whatsappUser?->user_id && (int) $whatsappUser->user_id !== $parentId) {
            return ParentLinkResult::notLinked('phone_conflict');
        }

        $this->syncWhatsAppUser($whatsappUser, $parentId, $schoolId, $phone);

        return new ParentLinkResult(
            linked: true,
            outcome: 'linked_existing_parent_record',
            parent: User::find($parentId),
            student: $student,
            studentName: $meta['name'],
            className: $meta['class'],
            schoolName: $meta['school'],
        );
    }

    private function createParentUser(string $senderName, int $studentId): User
    {
        $parent = User::create([
            'school_id' => null,
            'usergroup_id' => 7,
            'name' => $senderName ?: 'Parent',
            'email' => 'parent_'.$studentId.'_'.time().'@klassapp.sch.ug',
            'password' => bcrypt(Str::random(12)),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => null,
            'user_id' => $parent->id,
            'usergroup_id' => 7,
            'firstname' => $senderName ?: 'Parent',
            'lastname' => '',
            'status' => 'active',
        ]);

        return $parent;
    }

    private function createLink(int $parentId, int $studentId, int $schoolId): void
    {
        StudentParentLink::create([
            'school_id' => $schoolId,
            'parent_id' => $parentId,
            'student_id' => $studentId,
            'status' => 1,
        ]);
    }

    private function linkExists(int $parentId, int $studentId): bool
    {
        return StudentParentLink::query()
            ->where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('status', 1)
            ->exists();
    }

    private function syncWhatsAppUser(
        ?WhatsAppUser $whatsappUser,
        int $parentId,
        int $schoolId,
        ?string $phone = null,
    ): void {
        $payload = [
            'user_id' => $parentId,
            'school_id' => $schoolId,
            'opted_in' => true,
            'verified_at' => now(),
            'verified_via_schoolpay' => false,
        ];

        if ($whatsappUser) {
            $whatsappUser->update($payload);

            return;
        }

        WhatsAppUser::create(array_merge($payload, [
            'phone' => $phone,
        ]));
    }

    /**
     * @return array{name: string, class: string, school: string}
     */
    private function studentDisplayMeta(User $student): array
    {
        return [
            'name' => $student->name,
            'class' => $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A',
            'school' => $student->school?->name ?? 'the school',
        ];
    }
}
