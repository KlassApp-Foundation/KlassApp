<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Helpers\SiteHelper;

class NavigationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
       	$school_id = Auth::user()->school_id;

        $academic_year = AcademicYear::where('school_id',$school_id)->orderBy('name','ASC')->get();
       
        $current_year = SiteHelper::getAcademicYear($school_id);

        $array = [];

        $array['academiclist'] = $academic_year;

        $array['current_year'] = $current_year;

        return json_encode($array);
    }

    public function index(Request $request)
    {
        $academic_year_id=$request->academic_year_id;

        Cache::forget('academic_year');
        Cache::forget("academic_year_for_school_".Auth::user()->school_id);

        Cache::remember("academic_year", env('CACHE_TIME'), function () use ($academic_year_id) {
           return $academic_year_id;
        });
        
        $current_year = SiteHelper::getAcademicYear(Auth::user()->school_id);    
    }
}