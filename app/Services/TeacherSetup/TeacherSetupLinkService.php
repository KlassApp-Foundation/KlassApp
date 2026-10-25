<?php

namespace App\Services\TeacherSetup;

use App\Mail\TeacherSetupLinkMail;
use App\Models\School;
use App\Models\SchoolStaffLink;
use App\Models\StaffLinkRequest;
use App\Models\TeacherSetupToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        return DB::transaction(function () use ($school, $admin) {
            $this->revokeStaffLinks($school);

            $token = Str::random(48);

            SchoolStaffLink::query()->create([
                'school_id' => (int) $school->id,
                'token_hash' => $this->hash($token),
                'expires_at' => now()->addDays(self::STAFF_TTL_DAYS),
                'created_by' => $admin?->id,
            ]);

            return $token;
        });
    }

    public function revokeStaffLinks(School $school): void
    {
        SchoolStaffLink::query()
            ->where('school_id', (int) $school->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
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
     * Match a staff-link visitor to one active teacher at this school.
     *
     * Email is a full address. Phone is the full number: the same digits, or
     * the same national number with its country calling code. A short or
     * partial number matches nobody. Two teachers matching is nobody.
     */
    public function matchTeacher(School $school, ?string $email, ?string $phone): ?User
    {
        $email = mb_strtolower(trim((string) $email));

        $query = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active');

        if ($email !== '') {
            $matches = (clone $query)->whereRaw('lower(email) = ?', [$email])->limit(2)->get();
            if ($matches->count() > 1) {
                return null;
            }
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        if ($this->comparablePhone((string) $phone) === null) {
            return null;
        }

        $found = null;
        foreach ($query->get(['id', 'mobile_no']) as $candidate) {
            if (! $this->samePhone((string) $phone, (string) $candidate->mobile_no)) {
                continue;
            }
            if ($found !== null) {
                return null;
            }
            $found = (int) $candidate->id;
        }

        if ($found === null) {
            return null;
        }

        return User::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->whereKey($found)
            ->first();
    }

    /**
     * Queue a single-use setup link to the teacher's address on record.
     * Returns false when there is no address or the queue rejects it.
     * The visitor is never given the link, and a failed queue leaves no token.
     */
    public function queuePersonalLink(User $teacher, ?User $admin = null): bool
    {
        $email = trim((string) $teacher->email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $token = $this->issueFor($teacher, $admin);

        try {
            $schoolName = (string) (School::query()->whereKey($teacher->school_id)->value('name') ?: 'your school');
            Mail::to($email)->queue(new TeacherSetupLinkMail(
                name: trim((string) $teacher->name) !== '' ? trim((string) $teacher->name) : 'Teacher',
                schoolName: $schoolName,
                url: $this->personalUrl($token),
                expiresAt: now()->addDays(self::PERSONAL_TTL_DAYS),
            ));

            return true;
        } catch (\Throwable) {
            TeacherSetupToken::query()
                ->where('token_hash', $this->hash($token))
                ->where('user_id', $teacher->id)
                ->delete();
            Log::warning('Teacher setup link could not be queued', [
                'school_id' => (int) $teacher->school_id,
                'user_id' => (int) $teacher->id,
            ]);

            return false;
        }
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

    /**
     * Digits of a full phone number, with one trunk 0 removed.
     * Fewer than 8 or more than 15 digits is not a number we will match.
     */
    private function comparablePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) < 8 || strlen($digits) > 15 || preg_match('/^0+$/', $digits) === 1) {
            return null;
        }

        return $digits;
    }

    private function samePhone(string $left, string $right): bool
    {
        $a = $this->comparablePhone($left);
        $b = $this->comparablePhone($right);
        if ($a === null || $b === null) {
            return false;
        }
        if ($a === $b) {
            return true;
        }

        [$long, $short] = strlen($a) > strlen($b) ? [$a, $b] : [$b, $a];
        $prefixLen = strlen($long) - strlen($short);
        if ($prefixLen < 1 || $prefixLen > 3 || strlen($short) > 12) {
            return false;
        }
        if (strlen($long) < 11 || ! str_ends_with($long, $short)) {
            return false;
        }

        return preg_match('/^[1-9]\d{0,2}$/', substr($long, 0, $prefixLen)) === 1;
    }
}
