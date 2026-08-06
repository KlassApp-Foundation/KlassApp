<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DetailRequest;
use App\Helpers\SiteHelper;
use App\Models\Country;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Services\OnboardingStepsService;
use App\Traits\Common;
use App\Traits\LogActivity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SchoolDetailsController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $details = SchoolDetail::where('school_id', Auth::user()->school_id)->get()->keyby('meta_key');

        $school = School::where('id', Auth::user()->school_id)->first();

        return view('admin/schooldetails/index', ['details' => $details, 'school' => $school]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $array = [];

        $array['school_name'] = Auth::user()->school->name;
        $array['countrylist'] = SiteHelper::getCountries();
        $array['citylist'] = SiteHelper::getCities();

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin/schooldetails/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationStore(DetailRequest $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $school = School::where('id', Auth::user()->school_id)->first();

            $school->name = $request->name;
            $school->address = $request->address;
            $school->country_id = $request->country_id;
            $school->city_id = $request->city_id;

            $school->save();

            $file = $request->file('school_logo');
            if ($file > 0) {
                $folder = Auth::user()->school->slug.'/school_logo';
                $path = $this->uploadFile($folder, $file);

                $details = new SchoolDetail;

                $details->school_id = $school->id;
                $details->meta_key = 'school_logo';
                $details->meta_value = $path;

                $details->save();
            }

            foreach ($request->request as $key => $value) {
                $arrays = ['about_us', 'board', 'date_of_establishment', 'moto', 'school_logo', 'website'];
                foreach ($arrays as $array) {
                    if ($key == $array) {
                        $details = new SchoolDetail;

                        $details->school_id = $school->id;
                        $details->meta_key = $key;
                        $details->meta_value = $value;

                        $details->save();
                    }
                }
            }

            $message = trans('messages.add_success_msg', ['module' => 'School Details']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $details,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_ADD_SCHOOL_DETAIL,
                $message
            );

            return redirect()->back()->with('successmessage', $message);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

    public function edit($school_id)
    {
        $array = [];

        $school = School::where('id', $school_id)->first();
        $details = SchoolDetail::select('meta_key', 'meta_value')->where('school_id', $school_id)->get();
        $plucked = $details->pluck('meta_value', 'meta_key');

        $array['details'] = $plucked;
        $logo = $plucked['school_logo'] ?? null;
        $array['details']['school_logo_display'] = (! $logo || $logo === '-')
            ? null
            : $this->getFilePath($logo);
        $array['details']['name'] = $school->name;
        $array['details']['address'] = $school->address;
        $array['details']['country_id'] = $school->country_id;
        $array['details']['city_id'] = $school->city_id;
        $array['details']['registration_country'] = $school->registration_country;
        $array['details']['ministry_code'] = $school->ministry_code;
        $array['details']['uneb_center_number'] = $school->uneb_center_number;
        $array['details']['curriculum'] = $school->curriculum;
        if (empty($array['details']['board']) && filled($school->curriculum)) {
            $array['details']['board'] = $school->curriculum;
        }
        $array['details']['countrylist'] = SiteHelper::getCountries();
        $array['details']['citylist'] = SiteHelper::getCities();

        return $array;
    }

    public function editdetail($school_id)
    {
        $school = School::where('id', $school_id)->first();

        return view('/admin/schooldetails/edit', ['school_id' => $school_id, 'school' => $school]);
    }

    /**
     * Validate school-details update (axios preflight). Does not persist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationUpdate(DetailRequest $request, $school_id)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $school_id
     * @return \Illuminate\Http\Response
     */
    public function update(DetailRequest $request, $school_id)
    {
        try {
            $school_id = Auth::user()->school_id;
            $school = School::where('id', $school_id)->firstOrFail();
            $validated = $request->validated();

            $school->name = $validated['name'];
            if ($request->exists('address')) {
                $school->address = $request->input('address');
            }
            if (array_key_exists('city_id', $validated)) {
                $school->city_id = $validated['city_id'];
            }

            // Single country selector: write registration_country (Toshi) + country_id (FK).
            $country = Country::query()->find($validated['country_id']);
            if ($country) {
                OnboardingStepsService::persistCountry($school, $country->name);
                $school->refresh();
            } else {
                $school->country_id = $validated['country_id'];
            }

            if (array_key_exists('ministry_code', $validated)) {
                $school->ministry_code = $validated['ministry_code'] !== ''
                    ? $validated['ministry_code']
                    : null;
            }

            if (Schema::hasColumn('schools', 'uneb_center_number')
                && array_key_exists('uneb_center_number', $validated)) {
                $school->uneb_center_number = $validated['uneb_center_number'];
            }

            // Keep schools.curriculum aligned with the board selector (same vocabulary as Toshi).
            if (! empty($validated['board'])) {
                $school->curriculum = $validated['board'];
            }

            $school->save();

            $file = $request->file('school_logo');
            if ($file != null) {
                $folder = Auth::user()->school->slug.'/school_logo';
                $path = $this->uploadFile($folder, $file, 'public');

                SchoolDetail::updateOrCreate(
                    ['school_id' => $school_id, 'meta_key' => 'school_logo'],
                    ['meta_value' => $path]
                );
            }

            $metaKeys = ['about_us', 'board', 'date_of_establishment', 'moto', 'website'];
            foreach ($metaKeys as $metaKey) {
                if (! array_key_exists($metaKey, $validated) && ! $request->exists($metaKey)) {
                    continue;
                }

                $value = $validated[$metaKey] ?? $request->input($metaKey);
                if ($value === null) {
                    continue;
                }

                SchoolDetail::updateOrCreate(
                    ['school_id' => $school_id, 'meta_key' => $metaKey],
                    ['meta_value' => $value]
                );
            }

            $message = trans('messages.update_success_msg', ['module' => 'School Details']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $school,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_EDIT_SCHOOL_DETAIL,
                $message
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('successmessage', $message);
        } catch (Exception $e) {
            Log::info($e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('errormessage', $e->getMessage());
        }
    }
}
