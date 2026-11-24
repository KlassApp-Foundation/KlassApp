<?php

namespace App\Imports;

use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Link an additional guardian to a learner who already exists.
 *
 * This importer does not create learners and does not create standalone
 * parents: a guardian exists here because a child is already in the school.
 * That is the canonical model (a parent exists through a child), and it covers
 * the second-guardian case the student importer cannot: the student file has
 * one parent name and one phone column.
 *
 * Identify the learner with one of:
 *   kls | student_id | registration_number   (the KLS number), or
 *   student_firstname + student_lastname (+ class to disambiguate)
 *
 * Guardian columns: firstname, lastname, mobile_no, email, relationship.
 * An existing guardian in the same school (matched by phone, then email) is
 * reused instead of duplicated, so a parent with two children is one account.
 */
class ParentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $schoolId = (int) Auth::user()->school_id;
        $linked = 0;
        $failures = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            $first = trim((string) ($row['firstname'] ?? ''));
            $last = trim((string) ($row['lastname'] ?? ''));
            $mobile = trim((string) ($row['mobile_no'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($first === '' || ($mobile === '' && $email === '')) {
                $failures[] = "Row {$rowNumber}: guardian first name and either a phone number or an email are required.";

                continue;
            }

            $student = $this->resolveStudent($schoolId, $row, $rowNumber, $failures);
            if ($student === null) {
                continue;
            }

            try {
                $created = DB::transaction(function () use ($schoolId, $student, $first, $last, $mobile, $email, $row) {
                    $parent = $this->findOrCreateGuardian($schoolId, $first, $last, $mobile, $email);
                    if ($parent === null) {
                        return null;
                    }

                    $link = StudentParentLink::withTrashed()
                        ->where('school_id', $schoolId)
                        ->where('student_id', $student->id)
                        ->where('parent_id', $parent->id)
                        ->first();

                    if ($link !== null) {
                        if ($link->trashed()) {
                            $link->restore();
                        }
                        $link->forceFill(['status' => 'active'])->save();

                        return false;
                    }

                    StudentParentLink::create([
                        'school_id' => $schoolId,
                        'parent_id' => $parent->id,
                        'student_id' => $student->id,
                        'status' => 'active',
                    ]);

                    $relationship = trim((string) ($row['relationship'] ?? ''));
                    if ($relationship !== '' && $parent->wasRecentlyCreated) {
                        // Stored on the profile only when the guardian is new; an
                        // existing guardian's relationship may differ per child.
                        Userprofile::where('user_id', $parent->id)->update(['notes' => $relationship]);
                    }

                    return true;
                });
            } catch (\Throwable $e) {
                $failures[] = "Row {$rowNumber}: ".$e->getMessage();

                continue;
            }

            if ($created === null) {
                $failures[] = "Row {$rowNumber}: the guardian could not be created.";
            } elseif ($created === false) {
                $failures[] = "Row {$rowNumber}: this guardian is already linked to that learner.";
            } else {
                $linked++;
            }
        }

        Session::put('insertedcount', $linked);
        Session::put('import_row_failures', $failures);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveStudent(int $schoolId, Collection|array $row, int $rowNumber, array &$failures): ?User
    {
        $kls = trim((string) ($row['kls'] ?? $row['student_id'] ?? $row['registration_number'] ?? ''));

        if ($kls !== '') {
            $academic = StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('klassapp_student_id', $kls)
                ->first();

            if ($academic !== null && $academic->user !== null) {
                return $academic->user;
            }

            $byRegistration = User::query()
                ->where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->where('registration_number', $kls)
                ->first();

            if ($byRegistration !== null) {
                return $byRegistration;
            }

            $failures[] = "Row {$rowNumber}: no learner with that KLS number in this school.";

            return null;
        }

        $first = trim((string) ($row['student_firstname'] ?? ''));
        $last = trim((string) ($row['student_lastname'] ?? ''));
        $class = trim((string) ($row['class'] ?? ''));

        if ($first === '') {
            $failures[] = "Row {$rowNumber}: give the learner's KLS number, or their first and last name.";

            return null;
        }

        $query = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereHas('userprofile', function ($profile) use ($first, $last) {
                $profile->whereRaw('LOWER(firstname) = ?', [mb_strtolower($first)]);
                if ($last !== '') {
                    $profile->whereRaw('LOWER(lastname) = ?', [mb_strtolower($last)]);
                }
            });

        if ($class !== '') {
            $query->whereHas('studentAcademic.standardLink.section', function ($section) use ($class) {
                $section->whereRaw('LOWER(name) = ?', [mb_strtolower($class)]);
            });
        }

        $matches = $query->limit(3)->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $failures[] = $matches->isEmpty()
            ? "Row {$rowNumber}: no learner with that name in this school."
            : "Row {$rowNumber}: more than one learner has that name; add the KLS number or the class.";

        return null;
    }

    private function findOrCreateGuardian(int $schoolId, string $first, string $last, string $mobile, string $email): ?User
    {
        $existing = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 7)
            ->where(function ($query) use ($mobile, $email) {
                if ($mobile !== '') {
                    $query->where('mobile_no', $mobile);
                }
                if ($email !== '') {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->orderBy('id')
            ->first();

        if ($existing instanceof User) {
            return $existing;
        }

        $user = User::create([
            'school_id' => $schoolId,
            'usergroup_id' => 7,
            'name' => trim($first.' '.$last),
            'email' => $email !== '' ? $email : null,
            'mobile_no' => $mobile !== '' ? $mobile : null,
            'password' => bcrypt(Str::random(24)),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'usergroup_id' => 7,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);

        return $user;
    }
}
