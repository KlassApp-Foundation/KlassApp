<?php

namespace App\Imports;

use App\Helpers\SiteHelper;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\Subscription;
use App\Models\StandardLink;
use App\Models\Qualification;
use App\Models\AcademicYear;
use App\Traits\RegisterUser;
use App\Models\Country;
use App\Traits\Common;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Session;

class UsersImport implements ToCollection, WithHeadingRow
{
    use RegisterUser, Common;

    public function collection(Collection $rows)
    {
        try {
            $school_id     = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);

            $insertedcount = 0;
            $skippedcount = 0;
            $rowNumber = 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $row = collect($row)->filter(fn($value, $key) => is_string($key))->map(fn($v) => is_string($v) ? trim($v) : $v);
                // $row = collect($row)->filter(function ($value, $key) {
                //     return is_string($key); // removes numeric keys like 11 => 11
                // });
            // dd(array_keys($row->toArray()));
                // ✅ Minimal required fields
                $name = trim((string) ($row['firstname'] ?? $row['name'] ?? ''));
                $class = trim((string) ($row['class'] ?? ''));
                $stream = trim((string) ($row['stream'] ?? ''));
                $file = trim((string) ($row['file'] ?? ''));

                if ($name === '' || $class === '') {
                    Log::warning('Skipping invalid row', $row->toArray());
                    $skippedcount++;
                    continue;
                }

                // ✅ Init objects (IMPORTANT)
                $student = new \stdClass();
                $parent  = new \stdClass();

                /*
                |--------------------------------------------------------------------------
                | BASIC STUDENT DATA        
                |--------------------------------------------------------------------------
                */
                $student->name          = $name;
                $student->firstname     = $name;
                $student->lastname      = $row['lastname'] ?? null;
                $student->mobile_no = !empty($row['mobile_no']) ? 
                preg_replace('/[^0-9]/', '', $row['mobile_no']) : null;
                $student->email        = !empty($row['email']) ? strtolower($row['email']) : null;
                $student->gender       = !empty($row['gender']) ? strtolower($row['gender']) : null;

                // Safe date parsing
                $student->date_of_birth = !empty($row['date_of_birth'])
                    ? Carbon::parse($row['date_of_birth'])->format('Y-m-d')
                    : null;

                /*
                |--------------------------------------------------------------------------
                | LOCATION (UG DEFAULT FRIENDLY)
                |--------------------------------------------------------------------------
                */
                $country = !empty($row['country'])? Country::where('name', 'LIKE', '%' . $row['country'] . '%')->first() : null;

                // $city    = !empty($row['city']) ? City::where('name', 'LIKE', '%' . $row['city'] . '%')->first() : null;
                $city    = !empty($row['district']) ? City::where('name', 'LIKE', '%' . $row['district'] . '%')->first() : null;

                $student->country_id = $country->id ?? null;
        $student->city_id     = $city->id ?? null;
                $student->address    = $row['address'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | CLASS / SECTION (SAFE)
                |--------------------------------------------------------------------------
                */
                $sectionVal = trim($class);
                $standardLink = StandardLink::where('school_id', $school_id)
                    ->where('academic_year_id', $academic_year->id ?? null)
                    ->whereHas('section', function ($query) use ($school_id, $sectionVal) {
                        $query->where('school_id', $school_id)
                            ->whereRaw('LOWER(name) = ?', [strtolower($sectionVal)]);
                    })
                    ->first();

                if ($standardLink && $stream !== '') {
                    $standardLink->stream = $stream;
                    $standardLink->save();
                }

                $student->standard = $standardLink->id ?? null;
                $student->file = $file !== '' ? $file : null;

                /*
                |--------------------------------------------------------------------------
                | OPTIONAL FIELDS (IGNORED IF EMPTY)
                |--------------------------------------------------------------------------
                */
                $student->joining_date  = !empty($row['joining_date'])
                    ? Carbon::parse($row['joining_date'])->format('Y-m-d')
                    : null;

                $student->registration_number = $row['admission_number'] ?? null;
                $student->lin         = $row['lin'] ?? $row['LIN'] ?? null;
                $student->std_school_pay_number         = $row['std_school_pay_number'] ?? null;
                $student->notes               = $row['notes'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | PARENT (SAFE + OPTIONAL)
                |--------------------------------------------------------------------------
                */
                $parentPhone = $row['parent_mobile_no'] ?? $row['parent_phone'] ?? null;
                $parent_status = !empty($parentPhone)
                    ? User::where([
                        ['school_id', $school_id],
                        ['mobile_no', $parentPhone],
                        ['usergroup_id', 7]
                    ])->first()
                    : null;

                if (!$parent_status && !empty($parentPhone)) {

                    $parent->parent        = 'add';
                    $parent->firstname     = $row['parent_firstname'] ?? null;
                    $parent->lastname      = $row['parent_lastname'] ?? null;
                    $parent->mobile_no     = $parentPhone;
                    $parent->alternate_no  = $row['parent_alternate_no'] ?? null;
                    $parent->email         = $row['parent_email'] ?? null;
                    $parent->profession    = $row['parent_occupation'] ?? null;
                    $parent->designation   = $row['parent_designation'] ?? null;
                    $parent->relation      = $row['relation'] ?? 'guardian';

                } elseif ($parent_status) {

                    $parent->parent    = 'select';
                    $parent->select_id = $parent_status->id;
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE USER
                |--------------------------------------------------------------------------
                */
                $avatar = '';

                $student = $this->CreateUser(
                    $student,
                    $school_id,
                    $academic_year->id ?? null,
                    $avatar,
                    6
                );

                if (!empty($parentPhone)) {
                    $this->CreateParent($student->id, $parent, $school_id, 7);
                }

                $insertedcount++;
            }

            Session::put('insertedcount', $insertedcount);
            Session::put('skippedcount', $skippedcount);

        } catch (Exception $e) {
            Log::error('Import Error', [
                'exception' => $e,
                'school_id' => $school_id ?? null,
                'row' => $rowNumber,
            ]);

            throw new Exception("Row {$rowNumber}: {$e->getMessage()}", 0, $e);
        }
    }
}