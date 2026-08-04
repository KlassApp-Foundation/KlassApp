<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Usergroup;
use App\Models\Userprofile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Light SaaS signup bootstrap: admin user + placeholder school.
 *
 * Intentionally does NOT run AgentToshi::commitAll('create') or create an AcademicYear.
 * Curriculum is set to null so OnboardingStepsService treats it as unanswered.
 */
class SchoolSignupBootstrapService
{
    /**
     * @param  array{
     *   name: string,
     *   email: string,
     *   phone: ?string,
     *   password?: ?string,
     *   google_id?: ?string,
     *   google_avatar?: ?string,
     *   email_verified?: bool
     * }  $data
     */
    public function bootstrap(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $adminName = trim((string) $data['name']);
            $email = trim((string) $data['email']);
            $phone = $this->normalizePhone($data['phone'] ?? null);

            $school = $this->createPlaceholderSchool($adminName, $email, $phone);
            $user = $this->createSchoolAdmin($school, $data, $phone);
            $this->createUserProfile($user, $adminName, $phone);
            $this->createPendingSubscription($user);

            Log::info('SaaS minimal signup bootstrap complete', [
                'user_id' => $user->id,
                'school_id' => $school->id,
                'school_name' => $school->name,
            ]);

            return $user->fresh(['school', 'userprofile']);
        });
    }

    public function uniquePlaceholderSchoolName(string $adminName): string
    {
        $firstName = $this->firstNameFrom($adminName);
        $base = "{$firstName}'s School";

        return $this->uniqueSchoolName($base);
    }

    public function uniqueSchoolName(string $desired): string
    {
        $desired = trim($desired);
        if ($desired === '') {
            $desired = 'New School';
        }

        if (! School::where('name', $desired)->exists()) {
            return $desired;
        }

        $suffix = 2;
        while (School::where('name', "{$desired}-{$suffix}")->exists()) {
            $suffix++;
        }

        return "{$desired}-{$suffix}";
    }

    public function firstNameFrom(string $fullName): string
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
        if ($fullName === '') {
            return 'Admin';
        }

        $parts = explode(' ', $fullName);

        return $parts[0] ?: 'Admin';
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (str_starts_with($digits, '256') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (preg_match('/^7[0578]\d{7}$/', $digits)) {
            return '+256'.$digits;
        }

        if (str_starts_with($trimmed, '+') && strlen($digits) >= 9) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    private function createPlaceholderSchool(string $adminName, string $email, ?string $phone): School
    {
        $name = $this->uniquePlaceholderSchoolName($adminName);

        $payload = [
            'name' => $name,
            'email' => $email,
            // NULL (not '') — schools.phone has a UNIQUE index; empty string collides.
            'phone' => $phone,
            'slug' => Str::slug($name),
            'status' => '1',
            'toshi_enabled' => 1,
            'curriculum' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        // Force null past the column default ('uneb') when the DB still has a default.
        $school = new School($payload);
        $school->curriculum = null;
        $school->save();

        // Re-assert after observer saves (slug), in case MySQL default re-applied.
        if ($school->curriculum !== null) {
            DB::table('schools')->where('id', $school->id)->update(['curriculum' => null]);
            $school->refresh();
        }

        return $school;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSchoolAdmin(School $school, array $data, ?string $phone): User
    {
        $groupId = $this->resolveSchoolAdminGroupId();
        $password = $data['password'] ?? null;

        $userData = [
            'school_id' => $school->id,
            'usergroup_id' => $groupId,
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'mobile_no' => $phone,
            'password' => Hash::make($password ?: Str::random(32)),
            'email_verification_code' => Str::random(40),
            'email_verified' => 1,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if (Schema::hasColumn('users', 'is_activated')) {
            $userData['is_activated'] = 1;
        }

        $user = User::create($userData);

        if (! empty($data['google_id']) && Schema::hasColumn('users', 'google_id')) {
            $user->forceFill([
                'google_id' => $data['google_id'],
                'google_avatar' => Schema::hasColumn('users', 'google_avatar') ? ($data['google_avatar'] ?? null) : null,
                'google_token' => Schema::hasColumn('users', 'google_token') ? '' : null,
            ])->save();
        }

        return $user;
    }

    private function createUserProfile(User $user, string $adminName, ?string $phone): void
    {
        $payload = [
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'usergroup_id' => $user->usergroup_id,
            'firstname' => $adminName,
            'avatar' => $user->google_avatar ?: 'uploads/male.png',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        // Production userprofiles has no mobile_no column; only set when present.
        if (Schema::hasColumn('userprofiles', 'mobile_no')) {
            $payload['mobile_no'] = $phone;
        }

        Userprofile::create($payload);
    }

    private function createPendingSubscription(User $user): void
    {
        try {
            $planId = Plan::query()->orderBy('id')->value('id');
            if (! $planId) {
                return;
            }

            Subscription::create([
                'school_id' => $user->school_id,
                'user_id' => $user->id,
                'plan_id' => (int) $planId,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('SaaS signup: subscription skipped', ['message' => $e->getMessage()]);
        }
    }

    private function resolveSchoolAdminGroupId(): int
    {
        if (DB::table('usergroups')->where('id', 3)->exists()) {
            return 3;
        }

        $nameMatchId = Usergroup::query()
            ->whereRaw('LOWER(name) IN (?, ?, ?)', ['schooladmin', 'school admin', 'school administrator'])
            ->value('id');

        if ($nameMatchId) {
            return (int) $nameMatchId;
        }

        $any = (int) (DB::table('usergroups')->orderBy('id')->value('id') ?? 3);

        return $any > 0 ? $any : 3;
    }
}
