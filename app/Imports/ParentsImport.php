<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ParentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $schoolId = (int) Auth::user()->school_id;
        $inserted = 0;
        $failures = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            $first = trim((string) ($row['firstname'] ?? ''));
            $last = trim((string) ($row['lastname'] ?? ''));
            $mobile = trim((string) ($row['mobile_no'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($first === '' || $mobile === '') {
                $failures[] = "Row {$rowNumber}: first name and mobile number are required.";

                continue;
            }

            $duplicate = User::query()
                ->where('school_id', $schoolId)
                ->where('usergroup_id', 7)
                ->where(function ($query) use ($mobile, $email) {
                    $query->where('mobile_no', $mobile);
                    if ($email !== '') {
                        $query->orWhere('email', $email);
                    }
                })
                ->exists();

            if ($duplicate) {
                $failures[] = "Row {$rowNumber}: a parent with this phone or email is already in this school.";

                continue;
            }

            try {
                DB::transaction(function () use ($schoolId, $first, $last, $mobile, $email) {
                    $user = User::create([
                        'school_id' => $schoolId,
                        'usergroup_id' => 7,
                        'name' => trim($first.' '.$last),
                        'email' => $email !== '' ? $email : null,
                        'mobile_no' => $mobile,
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
                });
                $inserted++;
            } catch (\Throwable $e) {
                $failures[] = "Row {$rowNumber}: ".$e->getMessage();
            }
        }

        Session::put('insertedcount', $inserted);
        Session::put('import_row_failures', $failures);
    }
}
