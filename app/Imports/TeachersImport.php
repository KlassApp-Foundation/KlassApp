<?php

namespace App\Imports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\Qualification;
use App\Models\AcademicYear;
use App\Traits\RegisterUser;
use App\Models\Country;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Session;

class TeachersImport implements ToCollection, WithHeadingRow
{
    use RegisterUser;

    public function collection(Collection $rows)
    {
        try {

            $school_id     = Auth::user()->school_id;
            $academic_year = AcademicYear::where('school_id', $school_id)
                                ->where('status', 1)
                                ->first();

            $insertedcount = 0;
            $country = \App\Models\School::query()->whereKey($school_id)->value('registration_country');
            $country = is_string($country) ? $country : null;

            foreach ($rows as $row) {

                // ✅ Skip empty rows
                if (empty($row['firstname'] ?? null)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | LOCATION
                |--------------------------------------------------------------------------
                */
                $country = !empty($row['country'] ?? null)
                    ? Country::where('name', 'LIKE', '%' . $row['country'] . '%')->first()
                    : null;



                $city = !empty($row['district'] ?? null)
                    ? City::where('name', 'LIKE', '%' . $row['district'] . '%')->first()
                    : null;

                /*
                |--------------------------------------------------------------------------
                | QUALIFICATIONS - Now Safe
                |--------------------------------------------------------------------------
                */
                $qualification_array = [];

                // Additional Courses (if column exists)
                if (!empty($row['additional_coures'] ?? null)) {
                    $courses = array_map('trim', str_getcsv($row['additional_coures'], ',', '"', '\\'));

                    foreach ($courses as $course) {
                        $q = Qualification::where('type', 'teacher')
                            ->where('display_name', 'LIKE', '%' . $course . '%')
                            ->value('id');

                        if ($q) {
                            $qualification_array[] = $q;
                        }
                    }
                }

                // UG Degree (Safe)
                $ug_degree = null;
                if (!empty($row['ug_degree'] ?? null)) {
                    $ug_degree = Qualification::where('type', 'ug')
                        ->where('display_name', 'LIKE', '%' . $row['ug_degree'] . '%')
                        ->value('id');
                }

                // PG Degree (Safe)
                $pg_degree = null;
                if (!empty($row['pg_degree'] ?? null)) {
                    $pg_degree = Qualification::where('type', 'pg')
                        ->where('display_name', 'LIKE', '%' . $row['pg_degree'] . '%')
                        ->value('id');
                }

                /*
                |--------------------------------------------------------------------------
                | EMPLOYEE ID
                |--------------------------------------------------------------------------
                */
                $employee_id = !empty($row['employee_id'] ?? null)
                    ? $row['employee_id']
                    : 'EMP' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

                /*
                |--------------------------------------------------------------------------
                | BUILD TEACHER OBJECT
                |--------------------------------------------------------------------------
                */
                $teacher = new \stdClass();
                $teacher->firstname        = $row['firstname'];
                $teacher->lastname         = $row['lastname'] ?? null;
                $teacher->mobile_no        = \App\Support\PhoneCountry::normalise((string) ($row['mobile_no'] ?? ''), $country);
                $teacher->email            = !empty($row['email'] ?? null) ? strtolower($row['email']) : null;
                $teacher->gender           = $row['gender'] ?? null;

                $teacher->date_of_birth    = !empty($row['date_of_birth'] ?? null)
                    ? Carbon::parse($row['date_of_birth'])->format('Y-m-d')
                    : null;

                $teacher->address          = $row['address'] ?? null;
                $teacher->city_id          = $city->id ?? null;
                $teacher->country_id       = $country->id ?? null;

                $teacher->joining_date     = !empty($row['joining_date'] ?? null)
                    ? Carbon::parse($row['joining_date'])->format('Y-m-d')
                    : null;

                $teacher->employee_id      = $employee_id;
                $teacher->specialization   = $row['specialization'] ?? null;

                $teacher->qualification_id = $qualification_array[0] ?? 1;
                $teacher->sub_qualification = $row['sub_additional_coures'] ?? null;

                $teacher->designation      = $row['designation'] ?? null;
                $teacher->notes            = $row['notes'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | DUPLICATE CHECK
                |--------------------------------------------------------------------------
                */
                $exists = User::where('school_id', $school_id)
                    ->where(function ($query) use ($teacher, $employee_id, $country, $school_id) {
                        $query->whereHas('teacherprofile', function ($q) use ($employee_id) {
                            $q->where('employee_id', $employee_id);
                        });
                        if (! empty($teacher->mobile_no)) {
                            $query->orWhere(function ($phone) use ($teacher, $country, $school_id) {
                                $phone->matchingPhone((string) $teacher->mobile_no, $country, (int) $school_id);
                            });
                        }
                    })
                    ->exists();

                if (!$exists) {
                    $this->CreateTeacher($teacher, $school_id, $academic_year, "", 5);
                    $insertedcount++;
                }
            }

            Session::put('insertedcount', $insertedcount);

        } catch (Exception $e) {
            Log::error('Teacher Import Error: ' . $e->getMessage());
            dd($e->getMessage()); // Remove dd() in production
        }
    }
}