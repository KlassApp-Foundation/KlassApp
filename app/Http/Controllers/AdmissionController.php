<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers;

use App\Http\Requests\Admission\AdmissionAcademicRequest;
use App\Http\Requests\Admission\AdmissionStandardRequest;
use App\Http\Requests\Admission\AdmissionPersonalRequest;
use App\Http\Requests\Admission\AdmissionStudentRequest;
use App\Http\Requests\Admission\AdmissionAvatarRequest;
use App\Http\Requests\Admission\AdmissionParentRequest;
use App\Http\Resources\Admission as AdmissionResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AdmissionUser;
use App\Models\SchoolDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Userprofile;
use App\Models\Admission;
use App\Models\Standard;
use App\Traits\Common;
use App\Models\School;
use App\Models\User;
use Exception;
use Log;

class AdmissionController extends Controller
{  
    use AdmissionUser;
    use LogActivity;
    use Common;

    public function list(Request $request,$slug)
    {
        $school=School::where('slug',$slug)->first();

        $academic_year  = SiteHelper::getAcademicYear($school->id);
        $date_of_birth  = date('Y-m-d',strtotime('-25 years',strtotime(date('Y'))));

        $array = [];

        $array['transportList']     = SiteHelper::getTransportList();
        $array['standardlist']      = SiteHelper::getStandardList($school->id);
        $array['blood_group_list']  = SiteHelper::getBloodGroups();
        $array['qualificationlist'] = SiteHelper::getQualifications();

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($slug)
    {
        $school = School::where('slug', $slug)->first();

        if (! $school) {
            abort(404);
        }

        try {
            $admission_open = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'admission_open')->first();

            $logo = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'school_logo')->first();

            $closedetails = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'admission_close_message')->first();

            $boarding = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'boarding_available')->value('meta_value');
        } catch (\Throwable $e) {
            report($e);

            return response()->view('pages.admission.unavailable', ['slug' => $slug], 503);
        }

        $logoValue = $logo?->meta_value;

        return view('/pages/admission/admission', [
            'admission_open' => $admission_open,
            'isOpen'         => ($admission_open?->meta_value === '1'),
            'closedetails'   => $closedetails,
            'slug'           => $slug,
            'logo'           => (filled($logoValue) && $logoValue !== '-' ? ($logo->LogoPath ?? '') : ''),
            'boardingAvailable' => ($boarding === '1'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationStandard(AdmissionStandardRequest $request)
    {
        //  
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationStudentDetail(AdmissionStudentRequest $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationAcademicDetail(AdmissionAcademicRequest $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationParentDetail(AdmissionParentRequest $request)
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationPersonalDetail(AdmissionPersonalRequest $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $slug)
    {
        $school = School::where('slug', $slug)->first();

        if (! $school) {
            abort(404);
        }

        try {
            return $this->storeAdmission($request, $school);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'We could not submit the application right now. Please try again in a few minutes.');
        }
    }

    /**
     * Persist the admission form submission.
     */
    private function storeAdmission(Request $request, School $school)
    {
        $academic_year  = SiteHelper::getAcademicYear($school->id);
        try
        {
            $admin = User::where('school_id',$school->id)->ByRole(3)->first();
       
            $admission = new Admission;

            $admission->school_id           = $school->id;
            $admission->academic_year_id    = $academic_year->id;
            $admission->standard_id         = $request->standard_id;
            $admission->entry_term           = $request->entry_term;
            $admission->entry_year           = $request->entry_year;
            $admission->boarding_type        = $request->boarding_type;
            $admission->name                = $request->name;
            $admission->date_of_birth       = $request->date_of_birth;
            $file=$request->avatar;
            if($file)
            {
                $file_name  = $request->avatar->getClientOriginalName();
                $folder     = $school->id.'/student/avatar';
                $path       = $this->uploadFile($folder,$file);
                
                $admission->avatar=$path;
            }

            $birthcert=$request->birth_certificate;
            if($birthcert)
            {
                $birthcert_name = $request->birth_certificate->getClientOriginalName();
                $folder         = $school->id.'/student/documents';
                $birthcert_path = $this->uploadFile($folder,$birthcert);

                $admission->birth_certificate = $birthcert_path;
            }
            $admission->gender                      = $request->gender;
            $admission->height                      = $request->height;
            $admission->weight                      = $request->weight;
            $admission->birth_place                 = $request->birth_place;
            $admission->nationality                 = $request->nationality;
            $admission->religion                    = $request->religion;
            $admission->home_district               = $request->home_district;
            $admission->village_town                = $request->village_town;
            $admission->lin                         = $request->lin;
            $admission->mother_tongue               = $request->mother_tongue;
            $admission->identification_marks        = $request->identification_marks;
            $admission->blood_group                 = $request->blood_group;
            $admission->school_last_studied         = $request->school_last_studied;
            $admission->reason_for_leaving          = $request->reason_for_leaving;
            $admission->permanent_address           = $request->permanent_address;
            $admission->address_for_communication   = $request->address_for_communication;
            $admission->siblings                    = $request->siblings;
            //$admission->siblings_details          = $request->siblings_details;

            $array=[];

            $array['english']   = $request->english; 
            $array['maths']     = $request->maths;
            $array['science']   = $request->science;
            $array['social']    = $request->social;

            $admission->half_yearly_mark_details  = $array;

            $admission->last_class_completed      = $request->last_class_completed;
            $admission->ple_index_number          = $request->ple_index_number;
            $admission->ple_aggregate             = $request->ple_aggregate;
            $admission->uce_index_number          = $request->uce_index_number;
            $admission->uce_results_summary       = $request->uce_results_summary;

            $admission->board_of_education        = $request->board_of_education;
            $admission->choice_of_language        = $request->choice_of_language;
            $admission->group_selection           = $request->group_selection;
            $admission->father_name               = $request->father_name;
            $admission->father_relationship       = $request->father_relationship;
            $admission->father_mobile_no          = $request->father_mobile_no;
            $admission->father_on_whatsapp        = $request->has('father_on_whatsapp') ? $request->boolean('father_on_whatsapp') : null;
            $admission->father_alt_phone          = $request->father_alt_phone;
            $admission->father_email              = $request->father_email;
            $admission->father_occupation         = $request->father_occupation;
            $admission->father_district           = $request->father_district;

            $admission->mother_name               = $request->mother_name;
            $admission->mother_relationship       = $request->mother_relationship;
            $admission->mother_mobile_no          = $request->mother_mobile_no;
            $admission->mother_on_whatsapp        = $request->has('mother_on_whatsapp') ? $request->boolean('mother_on_whatsapp') : null;
            $admission->mother_alt_phone          = $request->mother_alt_phone;
            $admission->mother_email              = $request->mother_email;
            $admission->mother_occupation         = $request->mother_occupation;
            $admission->mother_district           = $request->mother_district;

            $admission->emergency_contact_name_1        = $request->emergency_contact_name_1;
            $admission->emergency_contact_1             = $request->emergency_contact_1;
            $admission->relation_with_student_1         = $request->relation_with_student_1;

            $admission->medical_conditions              = $request->medical_conditions;
            $admission->special_needs                   = $request->special_needs;

            $admission->application_status      = 'Draft';
            $admission->application_no          = 'APP-FORM-'.date('YmdHis');

            $admission->save();

            $message = trans('messages.add_success_msg',['module' => 'Admission Form']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $admission,
                $admin,
                ['ip' => $ip],
                LOGNAME_ADD_ADMISSION_FORM,
                $message
            ); 

            return redirect()->back()->with('successmessage',$message);
        }
        catch(Exception $e)
        {
            Log::error('Admission form submit failed', ['error' => $e->getMessage()]);

            throw $e;
        }   
    }  
}