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

    public const RESEND_COOLDOWN_MINUTES = 10;

    public function issueFor(User $teacher, ?User $admin = null, ?int $ttlDays = null): string
    {
        return DB::transaction(function () use ($teacher, $admin, $ttlDays) {
            $this->cancelUnusedPersonalLinks($teacher);

            $token = Str::random(48);

            TeacherSetupToken::query()->create([
                'school_id' => (int) $teacher->school_id,
                'user_id' => (int) $teacher->id,
                'token_hash' => $this->hash($token),
                'expires_at' => now()->addDays($ttlDays ?? self::PERSONAL_TTL_DAYS),
                'created_by' => $admin?->id,
            ]);

            return $token;
        });
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

        if ($this->setupMailOnCooldown($teacher)) {
            return true;
        }

        $token = Str::random(48);
        $row = TeacherSetupToken::query()->create([
            'school_id' => (int) $teacher->school_id,
            'user_id' => (int) $teacher->id,
            'token_hash' => $this->hash($token),
            'expires_at' => now()->addDays(self::PERSONAL_TTL_DAYS),
            'created_by' => $admin?->id,
        ]);

        try {
            $schoolName = (string) (School::query()->whereKey($teacher->school_id)->value('name') ?: 'your school');
            Mail::to($email)->queue(new TeacherSetupLinkMail(
                name: trim((string) $teacher->name) !== '' ? trim((string) $teacher->name) : 'Teacher',
                schoolName: $schoolName,
                url: $this->personalUrl($token),
                expiresAt: now()->addDays(self::PERSONAL_TTL_DAYS),
            ));
            $this->cancelUnusedPersonalLinks($teacher, (int) $row->id);

            return true;
        } catch (\Throwable) {
            $row->delete();
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

    /**
     * Claim this link and cancel the teacher's other unused links.
     * A second claim, including one that loaded the row before this one wrote, returns false.
     */
    public function consumePersonal(TeacherSetupToken $token): bool
    {
        return DB::transaction(function () use ($token) {
            $locked = TeacherSetupToken::query()
                ->whereKey($token->id)
                ->where('school_id', $token->school_id)
                ->where('user_id', $token->user_id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isUsable()) {
                return false;
            }

            $claimed = TeacherSetupToken::query()
                ->whereKey($locked->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            if ($claimed !== 1) {
                return false;
            }

            TeacherSetupToken::query()
                ->where('school_id', $locked->school_id)
                ->where('user_id', $locked->user_id)
                ->whereKeyNot($locked->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $token->used_at = now();

            return true;
        });
    }

    /**
     * Mark this teacher's unused setup links as used, so the old URL stops working.
     */
    private function cancelUnusedPersonalLinks(User $teacher, ?int $exceptId = null): void
    {
        $query = TeacherSetupToken::query()
            ->where('school_id', (int) $teacher->school_id)
            ->where('user_id', (int) $teacher->id)
            ->whereNull('used_at');

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update(['used_at' => now()]);
    }

    private function setupMailOnCooldown(User $teacher): bool
    {
        return TeacherSetupToken::query()
            ->where('school_id', (int) $teacher->school_id)
            ->where('user_id', (int) $teacher->id)
            ->where('created_at', '>=', now()->subMinutes(self::RESEND_COOLDOWN_MINUTES))
            ->exists();
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
