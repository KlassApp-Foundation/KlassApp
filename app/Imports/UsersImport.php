<?php

namespace App\Imports;

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
use App\Models\Standard;
use App\Models\Section;
use App\Models\Country;
use App\Traits\Common;
use App\Models\State;
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
            $academic_year = AcademicYear::where('school_id', $school_id)
                                ->where('status', 1)
                                ->first();

            $insertedcount = 0;

            foreach ($rows as $row) {
                $row = collect($row)->filter(function ($value, $key) {
                    return is_string($key); // removes numeric keys like 11 => 11
                });
            // dd(array_keys($row->toArray()));
                // ✅ Minimal required fields
                if (empty($row['firstname']) || empty($row['class'])) {
                    Log::warning('Skipping invalid row', $row->toArray());
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
                $student->firstname     = $row['firstname'] ?? null;
                $student->lastname      = $row['lastname'] ?? null;
                $student->mobile_no    = !preg_replace('/[^0-9]/', '', $row['mobile_no']) ?? null;
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
                $state   = !empty($row['state']) ? State::where('name', 'LIKE', '%' . $row['state'] . '%')->first() : null;
                $city    = !empty($row['city']) ? City::where('name', 'LIKE', '%' . $row['city'] . '%')->first() : null;

                $student->country_id = $country->id ?? null;
                $student->state_id   = $state->id ?? null;
                $student->city_id    = $city->id ?? null;
                $student->address    = $row['address'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | CLASS / SECTION (SAFE)
                |--------------------------------------------------------------------------
                */
                $standard = null;
                $section = null;
                $sectionVal = strtolower($row["class"]);

                if($sectionVal){
                    // derrive standards from sections
                    if(str_starts_with($sectionVal, "p")){
                    $standard = Standard::where('school_id', $school_id)->where( 'name', 'primary')->first();
                    }
                    $prefixes = ['b', 'm', 't'];
                    if (collect($prefixes)->contains(fn($p) => str_starts_with($sectionVal, $p))) {
                        $standard = Standard::where('school_id', $school_id)->where('name', 'nursery')->first();
                    }
                    $alevel = ['Senior Five', 'Senior Six'];
                    $olevel =  ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'];
                    if(in_array($sectionVal, $alevel)){
                    $standard = Standard::where('school_id', $school_id)->where( 'name', 'a-level')->first();
                    }
                   if(in_array($sectionVal, $olevel)){
                    $standard = Standard::where('school_id', $school_id)->where( 'name', 'o-level')->first();
                    }
                    $section      = Section::where([['school_id', $school_id], ['name', 'LIKE', $sectionVal]])->first();

                }
              
                if ($standard && $section) {
                    $standardLink = StandardLink::where([
                        ['school_id', $school_id],
                        ['standard_id', $standard->id],
                        ['section_id', $section->id]
                    ])->first();

                    $student->standard = $standardLink->id ?? null;
                }

                /*
                |--------------------------------------------------------------------------
                | OPTIONAL FIELDS (IGNORED IF EMPTY)
                |--------------------------------------------------------------------------
                */
                $student->blood_group   = !empty($row['blood_group']) ? strtolower($row['blood_group']) : null;
                $student->joining_date  = !empty($row['joining_date'])
                    ? Carbon::parse($row['joining_date'])->format('Y-m-d')
                    : null;

                $student->registration_number = $row['admission_number'] ?? null;
                $student->roll_number         = $row['roll_number'] ?? null;
                $student->notes               = $row['notes'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | PARENT (SAFE + OPTIONAL)
                |--------------------------------------------------------------------------
                */
                $parent_status = !empty($row['parent_mobile_no'])
                    ? User::where([
                        ['school_id', $school_id],
                        ['mobile_no', $row['parent_mobile_no']],
                        ['usergroup_id', 7]
                    ])->first()
                    : null;

                if (!$parent_status && !empty($row['parent_mobile_no'])) {

                    $parent->parent        = 'add';
                    $parent->firstname     = $row['parent_firstname'] ?? null;
                    $parent->lastname      = $row['parent_lastname'] ?? null;
                    $parent->mobile_no     = $row['parent_mobile_no'];
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

                if (!empty($row['parent_mobile_no'])) {
                    $this->CreateParent($student->id, $parent, $school_id, 7);
                }

                $insertedcount++;
            }

            Session::put('insertedcount', $insertedcount);

        } catch (Exception $e) {
            Log::error('Import Error: ' . $e->getMessage());
            dd($e->getMessage());
        }
    }
}