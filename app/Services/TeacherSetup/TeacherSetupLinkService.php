<?php

namespace App\Services\TeacherSetup;

use App\Models\School;
use App\Models\SchoolStaffLink;
use App\Models\StaffLinkRequest;
use App\Models\TeacherSetupToken;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Personal and school-wide links for class-teacher self-setup.
 *
 * Personal links are single-use, hashed at rest, valid for 14 days and tied to
 * one teacher. The staff link is one per school, valid for 7 days; whoever
 * uses it is matched against the school's teacher list and otherwise waits for
 * an admin to approve them.
 */
class TeacherSetupLinkService
{
    public const PERSONAL_TTL_DAYS = 14;

    public const STAFF_TTL_DAYS = 7;

    public function issueFor(User $teacher, ?User $admin = null, ?int $ttlDays = null): string
    {
        $token = Str::random(48);

        TeacherSetupToken::query()->create([
            'school_id' => (int) $teacher->school_id,
            'user_id' => (int) $teacher->id,
            'token_hash' => $this->hash($token),
            'expires_at' => now()->addDays($ttlDays ?? self::PERSONAL_TTL_DAYS),
            'created_by' => $admin?->id,
        ]);

        return $token;
    }

    public function resolvePersonal(string $token): ?TeacherSetupToken
    {
        $row = TeacherSetupToken::query()->where('token_hash', $this->hash($token))->first();

        return $row && $row->isUsable() ? $row : null;
    }

    public function personalUrl(string $token): string
    {
        return url('/teacher/setup/'.$token);
    }

    public function issueStaffLink(School $school, ?User $admin = null): string
    {
        $token = Str::random(48);

        SchoolStaffLink::query()->create([
            'school_id' => (int) $school->id,
            'token_hash' => $this->hash($token),
            'expires_at' => now()->addDays(self::STAFF_TTL_DAYS),
            'created_by' => $admin?->id,
        ]);

        return $token;
    }

    public function resolveStaffLink(string $token): ?SchoolStaffLink
    {
        $row = SchoolStaffLink::query()->where('token_hash', $this->hash($token))->first();

        return $row && $row->isUsable() ? $row : null;
    }

    public function staffUrl(string $token): string
    {
        return url('/school/staff/'.$token);
    }

    /**
     * Match a staff-link visitor to the school's teacher list by email or phone.
     */
    public function matchTeacher(School $school, ?string $email, ?string $phone): ?User
    {
        $email = mb_strtolower(trim((string) $email));
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        $query = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active');

        if ($email !== '') {
            $match = (clone $query)->whereRaw('lower(email) = ?', [$email])->first();
            if ($match) {
                return $match;
            }
        }

        if ($digits !== '') {
            foreach ($query->get(['id', 'email', 'mobile_no']) as $candidate) {
                $candidateDigits = preg_replace('/\D/', '', (string) $candidate->mobile_no) ?? '';
                if ($candidateDigits !== '' && $candidateDigits === $digits) {
                    return User::find($candidate->id);
                }
                if ($candidateDigits !== '' && (str_ends_with($candidateDigits, $digits) || str_ends_with($digits, $candidateDigits))) {
                    return User::find($candidate->id);
                }
            }
        }

        return null;
    }

    public function requestApproval(School $school, ?string $name, ?string $email, ?string $phone): StaffLinkRequest
    {
        return StaffLinkRequest::query()->create([
            'school_id' => (int) $school->id,
            'name' => $name !== null ? trim($name) : null,
            'email' => $email !== null && trim($email) !== '' ? mb_strtolower(trim($email)) : null,
            'phone' => $phone !== null && trim($phone) !== '' ? trim($phone) : null,
            'status' => StaffLinkRequest::STATUS_PENDING,
        ]);
    }

    public function consumePersonal(TeacherSetupToken $token): void
    {
        $token->forceFill(['used_at' => now()])->save();
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
