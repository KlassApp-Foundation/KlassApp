<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Accountant;

use App\Http\Resources\WorkAnniversary as WorkAnniversaryResource;
use App\Http\Resources\Birthday as BirthdayResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Smstemplate;
use App\Models\Userprofile;
use App\Models\User;

class BirthdayController extends Controller
{
    //

    public function showBirthday()
    {
        //
        $birthday = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(6)
                    ->get();
                            
        $users = BirthdayResource::collection($birthday);
         
        return $users;
    }

    public function birthdayUser()
    {
        $array = [];

        $birthday = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(6)
                    ->get();
        $templates = Smstemplate::where('name','birthday_message')->get();

        $array['birthdaylist'] = $birthday;
        $array['templatelist'] = $templates;

        return $array;
    }

    public function birthday()
    {
        return view('/accountant/dashboard/birthday');
    }

    public function showBirthdayTeacher()
    {
        //
        $birthday = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(5)
                    ->get();
                            
        $users = BirthdayResource::collection($birthday);
         
        return $users;
    }

    public function birthdayTeacher()
    {
        $array = [];

        $birthday = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(5)
                    ->get();
        $templates = Smstemplate::where('name','birthday_message')->get();

        $array['birthdaylist'] = $birthday;
        $array['templatelist'] = $templates;

        return $array;
    }

    public function birthdayCreate()
    {
        return view('/accountant/dashboard/birthdayTeacher');
    }

    public function showWorkAnniversary()
    {
        //
        $workanniversary = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(joining_date, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(5)
                    ->get();
                            
        $workanniversary = WorkAnniversaryResource::collection($workanniversary);
         
        return $workanniversary;
    }

    public function workAnniversary()
    {
        $array = [];

        $workanniversary = Userprofile::with('user')
                    ->whereRaw("DATE_FORMAT(joining_date, '%m-%d') = DATE_FORMAT(now(),'%m-%d')")
                    ->where('school_id',Auth::user()->school_id)
                    ->ByRole(5)
                    ->get();
        $templates = Smstemplate::where('name','work_anniversary_message')->get();

        $array['workanniversarylist'] = $workanniversary;
        $array['templatelist'] = $templates;

        return $array;
    }

    public function workAnniversaryCreate()
    {
        return view('/accountant/dashboard/workAnniversary');
    }
}