<?php
/**
 * Trait for processing common
 */
namespace App\Traits;

use App\Http\Resources\ParentDetail as ParentDetailResource;
use App\Http\Resources\Teacher as TeacherResource;
use App\Http\Resources\Alumni as AlumniResource;
use App\Http\Resources\User as UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Log;

/**
 *
 * @class trait
 * Trait for MemberProcess Processes
 */
trait MemberProcess
{
    public function MemberFilter($request,$school_id,$usergroup_id,$status)
    {
        try
        {
            if($usergroup_id == 6 && $request->status == 'active')
            {
                $users = User::BySchool($school_id)->ByRole($usergroup_id)->ByStatus($status);
            }
            else
            {
                $users = User::BySchool($school_id)->ByRole($usergroup_id);
            }
           
            
            $alphabet = $request->alphabet ? $request->alphabet:'';
            if($alphabet)
            {
                $users =$users->ByFirstName($alphabet);     
            } 

            $standard = $request->standard;
            if($standard!='')
            {
                $users = $users->ByStandard($standard);
            }

            $status = $request->status;
            if($status!='')
            {
                $users = $users->ByStatus($status);
            }

            if($status=='')
            {
                $users = $users->where('status','!=','exit');
            }
            
            if(count((array)\Request::getQueryString())>0)
            {
                $firstname = $request->firstname;
                if($firstname!='')
                {
                    $users = $users->ByFirstName($firstname);
                }

                $lastname = $request->lastname;
                if($lastname!='')
                {
                    $users = $users->ByLastName($lastname);
                }

                $gender = $request->gender;
                if($gender!='')
                {
                    $users = $users->ByGender($gender);
                }

                $date_of_birth = $request->date_of_birth;
                if ($date_of_birth != '1970-01-01') 
                {
                    if($date_of_birth != '') 
                    {
                        $users = $users->ByDateOfBirth($date_of_birth); 
                    }
                }

                $mobile_no = $request->mobile_no;
                if($mobile_no!='')
                {
                    $users = $users->ByMobileNo($mobile_no);
                }

                $email = $request->email;
                if($email!='')
                {
                    $users = $users->ByEmailId($email);
                }

                $blood_group = $request->blood_group;
                if($blood_group!='')
                {
                    $users = $users->ByBloodGroup($blood_group);
                }

                $transport = $request->transport;
                if($transport!='')
                {
                    $users = $users->ByTransport($transport);
                }

                $caste = $request->caste;
                if($caste!='')
                {
                    $users = $users->ByCaste($caste);
                }
                $admission_number = $request->admission_number;
                if($admission_number!='')
                {
                    $users = $users->ByAdmissionNumber($admission_number);
                }
                $status = $request->status;
                if($status!='')
                {
                    $users = $users->ByStatus($status);
                }
            }
            $users=$users->get()->sortBy('userprofile.firstname');
            $users = UserResource::collection($users);
            return $users;
        }

        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }

    public function TeacherFilter($request,$school_id,$usergroup_id)
    {
        try
        {
            $users = User::where('school_id',$school_id)->ByRole($usergroup_id)->whereHas('userprofile', function($q){
                        $q->where('status','active')->orWhere('status','inactive');
                    });

            $alphabet = $request->alphabet ? $request->alphabet:'';
                if($alphabet)
                {
                    $users =$users->ByFirstName($alphabet);     
                } 
            if(count((array)\Request::getQueryString())>0)
            {
                $firstname = $request->firstname;
                if($firstname!='')
                {
                    $users = $users->ByFirstName($firstname);
                }

                $lastname = $request->lastname;
                if($lastname!='')
                {
                    $users = $users->ByLastName($lastname);
                }

                $gender = $request->gender;
                if($gender!='')
                {
                    $users = $users->ByGender($gender);
                }
                
               $date_of_birth = $request->date_of_birth;
                if ($date_of_birth != '1970-01-01') 
                {
                    if($date_of_birth != '') 
                    {
                        $users = $users->ByDateOfBirth($date_of_birth); 
                    }
                }

                $mobile_no = $request->mobile_no;
                if($mobile_no!='')
                {
                    $users = $users->ByMobileNo($mobile_no);
                }

                $email = $request->email;
                if($email!='')
                {
                    $users = $users->ByEmailId($email);
                }

                $blood_group = $request->blood_group;
                if($blood_group!='')
                {
                    $users = $users->ByBloodGroup($blood_group);
                }

                $qualification = $request->qualification;
                if($qualification!='')
                {
                    $users = $users->ByQualification($qualification);
                }

                $designation = $request->designation;
                if($designation!='')
                {
                    $users = $users->ByDesignation($designation);
                }

                $marital_status = $request->marital_status;
                if($marital_status!='')
                {
                    $users = $users->ByMaritalStatus($marital_status);
                }

                $job_type = $request->job_type;
                if($job_type!='')
                {
                    $users = $users->ByJopType($job_type);
                }
            }
            $users=$users->get();
            $users = TeacherResource::collection($users);
            return $users;
        }

        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }

    public function StaffFilter($request,$school_id,$usergroup_id)
    {
        try
        {
            $users = User::where('school_id',$school_id)->whereIn('usergroup_id',$usergroup_id)->whereHas('userprofile', function($q){
                        $q->where('status','active')->orWhere('status','inactive');
                    });
            
            $alphabet = $request->alphabet ? $request->alphabet:'';
                if($alphabet)
                {
                    $users =$users->ByFirstName($alphabet);     
                } 
            if(count((array)\Request::getQueryString())>0)
            {
                $firstname = $request->firstname;
                if($firstname!='')
                {
                    $users = $users->ByFirstName($firstname);
                }

                $lastname = $request->lastname;
                if($lastname!='')
                {
                    $users = $users->ByLastName($lastname);
                }

                $gender = $request->gender;
                if($gender!='')
                {
                    $users = $users->ByGender($gender);
                }
                
                $date_of_birth = $request->date_of_birth;
                if ($date_of_birth != '1970-01-01') 
                {
                    if($date_of_birth != '') 
                    {
                        $users = $users->ByDateOfBirth($date_of_birth); 
                    }
                }

                $mobile_no = $request->mobile_no;
                if($mobile_no!='')
                {
                    $users = $users->ByMobileNo($mobile_no);
                }

                $email = $request->email;
                if($email!='')
                {
                    $users = $users->ByEmailId($email);
                }

                $blood_group = $request->blood_group;
                if($blood_group!='')
                {
                    $users = $users->ByBloodGroup($blood_group);
                }

                $qualification = $request->qualification;
                if($qualification!='')
                {
                    $users = $users->ByQualification($qualification);
                }

                $designation = $request->designation;
                if($designation!='')
                {
                    $users = $users->ByDesignation($designation);
                }

                $marital_status = $request->marital_status;
                if($marital_status!='')
                {
                    $users = $users->ByMaritalStatus($marital_status);
                }

                $job_type = $request->job_type;
                if($job_type!='')
                {
                    $users = $users->ByJopType($job_type);
                }
            }
            $users=$users->get();
            $users = TeacherResource::collection($users);
            return $users;   
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    } 

    public function ParentFilter($request,$school_id,$usergroup_id)
    {
        try
        {
            $users = User::where('school_id',$school_id)->ByRole($usergroup_id)->whereHas('children', function($q) use ($search){
    
                $q->whereHas('userStudent', function($q) 
                {
                    $q->where([['status','!=','exit']]);
                });
            })->whereHas('userprofile', function($q){
                    $q->where('status','active')->orWhere('status','inactive');
                });

            if(count((array)\Request::getQueryString())>0)
            {
                $firstname = $request->firstname;
                if($firstname!='')
                {
                    $users = $users->ByFirstNameParent($firstname);
                }

                $fullname = $request->fullname;
                if($fullname!='')
                {
                    $users = $users->ByFullNameParent($fullname);
                }

                $student_name = $request->student_name;
                if($student_name!='')
                {
                    $users = $users->ByStudentNameParent($student_name);
                }

                $lastname = $request->lastname;
                if($lastname!='')
                {
                    $users = $users->ByLastNameParent($lastname);
                }

                $mobile_no = $request->mobile_no;
                if($mobile_no!='')
                {
                    $users = $users->ByMobileNoParent($mobile_no);
                }

                $email = $request->email;
                if($email!='')
                {
                    $users = $users->ByEmailParent($email);
                }

                $qualification = $request->qualification;
                if($qualification!='')
                {
                    $users = $users->ByQualificationParent($qualification);
                }

                $occupation = $request->occupation;
                if($occupation!='')
                {
                    $users = $users->ByOccupationParent($occupation);
                }

                $standardlink_id = $request->standardlink_id;
                if($standardlink_id!='')
                {
                    $users = $users->ByStandardLinkParent($standardlink_id);
                }
            }
            $users=$users->paginate(10);
            $users = ParentDetailResource::collection($users);
            return $users;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }  

    public function AlumniFilter($request,$school_id,$usergroup_id)
    {
        try
        {
            $users = User::where('school_id',$school_id)->ByRole($usergroup_id);

            $alphabet = $request->alphabet ? $request->alphabet:'';
            if($alphabet)
            {
                $users =$users->ByName($alphabet);     
            } 

            $passing_session = $request->passing_session;
            if($passing_session)
            {
                $users =$users->ByBatch($passing_session);     
            } 

            $users=$users->get();

            if(class_exists('Gegok12\Alumni\Http\Resources\Alumni')) //new
            {
                $users = \Gegok12\Alumni\Http\Resources\Alumni::collection($users);
            }
            else
            {
                $users = AlumniResource::collection($users);
            }

            return $users;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
   }

    public function AlumniProfileFilter($request,$school_id,$usergroup_id,$user_id)
    {
        try
        {
            $users = User::where('school_id',$school_id)->ByRole($usergroup_id)->where('id','!=',$user_id);

            $alphabet = $request->alphabet ? $request->alphabet:'';
            if($alphabet)
            {
                $users =$users->ByName($alphabet);     
            } 

            $passing_session = $request->passing_session;
            if($passing_session)
            {
                $users =$users->ByBatch($passing_session);     
            } 

            $users=$users->get();
            
            if(class_exists('Gegok12\Alumni\Http\Resources\Alumni')) //new
            {
                $users = \Gegok12\Alumni\Http\Resources\Alumni::collection($users);
            }
            else
            {
                $users = AlumniResource::collection($users);
            }

            return $users;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    } 
}