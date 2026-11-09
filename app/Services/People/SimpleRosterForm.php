<?php

namespace App\Services\People;

use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use App\Support\PhoneCountry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The student and parent edit forms ask only for the import columns.
 * Anything the import does not ask for is left as stored.
 */
final class SimpleRosterForm
{
    public function __construct(private OnboardingEngine $onboarding) {}

    /**
     * @return array<string, mixed>
     */
    public function studentForm(User $student): array
    {
        $student->loadMissing('userprofile');
        $academic = $this->academic($student);
        $academic?->loadMissing('standardLink.section');
        $link = $academic?->standardLink;
        $parent = $this->linkedParent($student);

        return [
            'name' => $this->displayName($student),
            'class' => trim((string) ($link?->section?->name ?? '')),
            'stream' => trim((string) ($link?->stream ?? '')),
            'gender' => strtolower(trim((string) ($student->userprofile?->gender ?? ''))),
            'date_of_birth' => $this->dobValue($student->userprofile?->date_of_birth),
            'parent_name' => $parent ? $this->displayName($parent) : '',
            'parent_phone' => (string) ($parent?->mobile_no ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateStudent(User $student, array $input): void
    {
        $school = School::query()->findOrFail((int) $student->school_id);
        $year = SiteHelper::getAcademicYear((int) $school->id);
        if ($year === null) {
            throw ValidationException::withMessages([
                'class' => 'Set the academic year before changing a class.',
            ]);
        }

        $name = $this->cleanName((string) ($input['name'] ?? ''));
        $className = trim((string) ($input['class'] ?? ''));
        $stream = trim((string) ($input['stream'] ?? ''));
        $gender = $this->gender($input['gender'] ?? null);
        $dob = $this->dob($input['date_of_birth'] ?? null);
        $parentName = $this->cleanName((string) ($input['parent_name'] ?? ''));
        $parentPhoneRaw = trim((string) ($input['parent_phone'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Enter the student\'s name.';
        }
        if ($className === '') {
            $errors['class'] = 'Enter a class from this school.';
        }
        if ($parentPhoneRaw !== '' && $this->phone($school, $parentPhoneRaw) === null) {
            $errors['parent_phone'] = 'Enter a phone number, such as +256700000000.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $link = $this->onboarding->resolveStandardLinkForClass($school, $year, $className, $stream !== '' ? $stream : null);
        if ($link === null || (int) $link->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'class' => 'That class and stream are not in this school year.',
            ]);
        }

        DB::transaction(function () use ($student, $school, $year, $name, $gender, $dob, $link, $parentName, $parentPhoneRaw): void {
            $this->rename($student, $name, $gender, $dob);

            $academic = $this->academic($student);
            if ($academic === null) {
                StudentAcademic::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'user_id' => $student->id,
                    'standardLink_id' => $link->id,
                ]);
            } else {
                $academic->standardLink_id = $link->id;
                $academic->save();
            }

            if ($parentName !== '' || $parentPhoneRaw !== '') {
                $this->linkParent($school, $student, $parentName, $parentPhoneRaw);
            }
        });
    }

    /**
     * @return array{name: string, phone: string, email: string}
     */
    public function parentForm(User $parent): array
    {
        return [
            'name' => $this->displayName($parent),
            'phone' => (string) ($parent->mobile_no ?? ''),
            'email' => $this->realEmail($parent),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateParent(User $parent, array $input): void
    {
        $school = School::query()->findOrFail((int) $parent->school_id);
        $name = $this->cleanName((string) ($input['name'] ?? ''));
        $phoneRaw = trim((string) ($input['phone'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Enter the parent\'s name.';
        }
        if ($phoneRaw === '' || $this->phone($school, $phoneRaw) === null) {
            $errors['phone'] = 'Enter a phone number, such as +256700000000.';
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email, or leave it blank.';
        }
        if ($email !== '' && User::query()->where('email', $email)->where('id', '!=', $parent->id)->exists()) {
            $errors['email'] = 'That email is already in use.';
        }
        if ($phoneRaw !== '') {
            $phone = $this->phone($school, $phoneRaw);
            $taken = $phone !== null && User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 7)
                ->where('id', '!=', $parent->id)
                ->where('mobile_no', $phone)
                ->exists();
            if ($taken) {
                $errors['phone'] = 'Another parent in this school already uses that phone.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($parent, $name, $phoneRaw, $school, $email): void {
            $this->rename($parent, $name, null, null);
            $parent->mobile_no = $this->phone($school, $phoneRaw);
            if ($email !== '') {
                $parent->email = $email;
            }
            $parent->save();
        });
    }

    private function linkParent(School $school, User $student, string $parentName, string $parentPhoneRaw): void
    {
        $phone = $parentPhoneRaw === '' ? '' : (string) $this->phone($school, $parentPhoneRaw);
        $parent = null;
        if ($phone !== '') {
            $parent = User::query()
                ->where('school_id', $school->id)
                ->where('usergroup_id', 7)
                ->where('mobile_no', $phone)
                ->first();
        }
        if ($parent === null) {
            $parent = $this->linkedParent($student);
        }

        if ($parent === null) {
            $parent = User::create([
                'school_id' => $school->id,
                'usergroup_id' => 7,
                'name' => $parentName !== '' ? $parentName : 'Parent',
                'email' => $this->placeholderEmail($school, $student),
                'password' => null,
                'mobile_no' => $phone !== '' ? $phone : null,
                'status' => 'active',
                'email_verified' => 1,
                'is_reset' => 1,
            ]);
            Userprofile::create([
                'school_id' => $school->id,
                'user_id' => $parent->id,
                'usergroup_id' => 7,
                'firstname' => $parentName !== '' ? $parentName : 'Parent',
                'lastname' => '',
            ]);
        } else {
            if ($parentName !== '') {
                $this->rename($parent, $parentName, null, null);
            }
            if ($phone !== '') {
                $parent->mobile_no = $phone;
                $parent->save();
            }
        }

        StudentParentLink::query()->firstOrCreate(
            [
                'school_id' => $school->id,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
            ],
            ['status' => 1]
        );
    }

    private function rename(User $user, string $name, ?string $gender, ?string $dob): void
    {
        [$first, $last] = $this->splitName($name);
        $user->name = $name;
        $user->save();

        $profile = $user->userprofile ?: new Userprofile([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'usergroup_id' => $user->usergroup_id,
        ]);
        $profile->firstname = $first;
        $profile->lastname = $last;
        if ($gender !== null) {
            $profile->gender = $gender;
        }
        if ($dob !== null) {
            $profile->date_of_birth = $dob;
        }
        $profile->save();
    }

    private function academic(User $student): ?StudentAcademic
    {
        $year = SiteHelper::getAcademicYear((int) $student->school_id);

        return StudentAcademic::query()
            ->where('school_id', $student->school_id)
            ->where('user_id', $student->id)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->orderByDesc('id')
            ->first();
    }

    private function linkedParent(User $student): ?User
    {
        $link = StudentParentLink::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->orderBy('id')
            ->first();
        if ($link === null) {
            return null;
        }

        return User::query()
            ->where('school_id', $student->school_id)
            ->where('usergroup_id', 7)
            ->whereKey($link->parent_id)
            ->first();
    }

    private function displayName(User $user): string
    {
        $profile = $user->userprofile;
        $fromProfile = trim((string) ($profile?->firstname ?? '').' '.(string) ($profile?->lastname ?? ''));

        return $fromProfile !== '' ? $fromProfile : trim((string) $user->name);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', $name) ?: [];
        $first = (string) array_shift($parts);

        return [$first, trim(implode(' ', $parts))];
    }

    private function cleanName(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    private function gender(mixed $value): ?string
    {
        $gender = strtolower(trim((string) $value));
        if ($gender === '') {
            return null;
        }
        if (in_array($gender, ['m', 'male', 'boy'], true)) {
            return 'male';
        }
        if (in_array($gender, ['f', 'female', 'girl'], true)) {
            return 'female';
        }

        throw ValidationException::withMessages([
            'gender' => 'Gender is female or male.',
        ]);
    }

    private function dobValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $text = trim((string) $value);

        return strlen($text) >= 10 ? substr($text, 0, 10) : '';
    }

    private function dob(mixed $value): ?string
    {
        $dob = trim((string) $value);
        if ($dob === '') {
            return null;
        }
        $parsed = \DateTime::createFromFormat('Y-m-d', $dob);
        if ($parsed === false || $parsed->format('Y-m-d') !== $dob) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Enter the date of birth as YYYY-MM-DD.',
            ]);
        }

        return $dob;
    }

    private function phone(School $school, string $raw): ?string
    {
        $country = trim((string) ($school->registration_country ?? '')) ?: 'Uganda';

        return PhoneCountry::normalise($raw, $country);
    }

    private function realEmail(User $user): string
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '' || str_ends_with($email, '.local') || str_ends_with($email, '.test')) {
            return '';
        }

        return $email;
    }

    private function placeholderEmail(School $school, User $student): string
    {
        return 'parent.'.$school->id.'.'.$student->id.'.'.uniqid().'@klassapp.local';
    }
}
