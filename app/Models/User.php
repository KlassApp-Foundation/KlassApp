<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
//use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia;
use Laracasts\Presenter\PresentableTrait;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\LaratrustUserTrait;
use Spatie\MediaLibrary\Models\Media;
use Laravel\Sanctum\HasApiTokens;
use App\Presenters\UserPresenter;
use App\Helpers\SiteHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements HasMedia
{
    use \Nckg\Impersonate\Traits\CanImpersonate;
    use LaratrustUserTrait;
    use PresentableTrait;
   // use HasMediaTrait;
    use InteractsWithMedia;
    use HasApiTokens;
    use SoftDeletes;
    use Notifiable;
    use HasFactory;

    protected $presenter = "App\Presenters\UserprofilePresenter";

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'usergroup_id' , 'ref_id' ,'name', 'email', 'password','mobile_no','is_activated', 'email_verification_code' , 'email_verified' , 'email_verified_at' , 'platform_token' , 'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $with=['userprofile' ,'members','children','parents'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at' , 'email_verified_at'];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function userprofile()
    {
        return $this->hasOne('App\Models\Userprofile','user_id','id');
    }

     public function librarycard()
    {
        return $this->hasOne('App\Models\LibraryCard','user_id','id');
    }

    public function roomlinks()
    {
        return $this->hasMany('App\Models\RoomLink','user_id','id');
    }

    public function members()
    {
        return $this->hasMany('App\Models\User','ref_id','id');
    }

    public function parents()
    {
        return $this->hasMany('App\Models\StudentParentLink','student_id','id');
    }

    public function father()
    {
      
         $father=$this->whereHas('parentprofile', function($query) {
              $query->where('relation', 'father');
          });
       
        return $father;
    }

    public function mother()
    {
      
         $mother=$this->members()->whereHas('parentprofile', function($query) {
              $query->where('relation', 'mother');
          });
        
        return $mother;
    }

    public function scopeByRelation($query ,$student_id, $relation)
    {
      
            $query->whereHas('userParent', function ($q) use($relation)
            {
                $q->whereHas('parentprofile', function ($qu) use($relation)
                {
                    $qu->where('relation',$relation);
                });
            });
       
        
        return $query;
    }

    public function children()
    {
        return $this->hasMany('App\Models\StudentParentLink','parent_id','id');
    }

    public function usergroup()
    {
        return $this->belongsTo('App\Models\Usergroup','usergroup_id');
    }

    public function activitylog()
    {
        return $this->hasMany('App\Models\ActivityLog','causer_id','id');
    }

     public function notes()
    {
        return $this->hasMany('App\Models\Notes','entity_id','id');
    }

    public function subscription()
    {
        return $this->hasMany('App\Models\Subscription','user_id','id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeBySchool($query,$school_id)
    {
       $query->where('school_id',$school_id);
       return $query;
    }

    public function scopeByRole($query,$usergroup_id)
   {
       $query->where('usergroup_id',$usergroup_id);
       return $query;
   }

    public function scopeByStatus($query , $status)
    {
        $query->wherehas('userprofile',function ($query) use($status)
        {
            $query->where('status',$status); 
        });
        return $query;
    }

    public function scopeByName($query , $name)
    {
        $query->where('name','LIKE',$name.'%'); 

        return $query;
    }

    public function scopeByFirstName($query , $firstname)
    {
        $query->wherehas('userprofile',function ($query) use($firstname)
            {
                $query->where('firstname','LIKE',$firstname.'%'); 
            });
        return $query;
    }

    public function scopeByLastName($query , $lastname)
    {
        $query->wherehas('userprofile',function ($query) use($lastname)
            {
                $query->where('lastname','LIKE',$lastname.'%'); 
            });
        return $query;
    }

    public function scopeByGender($query , $gender)
    {
        $query->wherehas('userprofile',function ($query) use($gender)
            {
                $query->where('gender','=',$gender); 
            });
        return $query;
    }

    public function scopeByMaritalStatus($query , $marital_status)
    {
        $query->wherehas('userprofile',function ($query) use($marital_status)
            {
                $query->where('marital_status','=',$marital_status); 
            });
        return $query;
    }

    public function scopeByDateOfBirth($query , $date_of_birth)
    {
        $query->wherehas('userprofile',function ($query) use($date_of_birth)
            {
                $query->where(\DB::raw("(DATE_FORMAT(date_of_birth,'%m'))"),$date_of_birth);
            });
        return $query;
    }

    public function scopeByMobileNo($query , $mobile_no)
    {
        $query->where('mobile_no','LIKE',$mobile_no.'%'); 
    
        return $query;
    }

    public function scopeByEmailId($query , $email)
    {
        $query->where('email','LIKE',$email.'%'); 
           
        return $query;
    }

    public function scopeByStandard($query , $standard)
    {
        $query->wherehas('studentAcademic',function ($query) use($standard)
            {
                $query->wherehas('standardLink',function ($query) use($standard)
                {
                    $query->where('id','=',$standard); 
                }); 
            });
        return $query;
    }

    public function scopeByTransport($query , $transport)
    {
        $query->wherehas('studentAcademic',function ($query) use($transport)
            {
                $query->where('mode_of_transport','=',$transport); 
            });
        return $query;
    }

    public function scopeByCaste($query , $caste)
    {
        $query->wherehas('userprofile',function ($query) use($caste)
            {
                $query->where('caste','=',$caste);
            });
        return $query;
    }

    public function scopeByAdmissionNumber($query , $admission_number)
    {
        $query->where('registration_number','LIKE',$admission_number.'%'); 
           
        return $query;
    }
    public function scopeByUserStatus($query , $status)
    {
        $query->where('status','LIKE',$status.'%'); 
           
        return $query;
    }


    public function sendMail()
    {
        return $this->hasMany('App\Models\SendMail','user_id','id');
    }

    public function permissionUser()
    {
        return $this->hasMany('App\Models\PermissionUser','user_id','id');
    }

    public function userReminder()
    {
        return $this->hasMany('App\Models\Reminder', 'id' ,'entity_id')->where('entity_name','=','App\\Models\\User');
    }
    
    public function feedbackParent() 
    {
        return $this->hasMany('App\Models\Feedback', 'parent_id', 'id');
    }

    public function feedbackAdmin() 
    {
        return $this->hasMany('App\Models\Feedback', 'admin_id', 'id');
    }

    public function studentAcademic()
    {
        return $this->hasMany('App\Models\StudentAcademic','user_id','id');
    }

    public function studentAcademicLatest()
    {
        return $this->hasOne('App\Models\StudentAcademic','user_id','id')->orderByDesc('id')->limit(1);
    }

    public function marks()
    {
        return $this->hasMany('App\Models\Mark','user_id','id');
    }
    
    public function teacherprofile()
    {
        return $this->hasMany('App\Models\TeacherProfile','user_id','id');
    }

    public function alumniprofile()
    {
        if(class_exists('Gegok12\Alumni\Models\Alumniprofile'))
        { 
            return $this->hasOne('\Gegok12\Alumni\Models\Alumniprofile','user_id','id');
        }
        else
        {
            return $this->hasOne('App\Models\Alumniprofile','user_id','id');
        }
    }
    
    public function reportUser()
    {
        return $this->hasMany('App\Models\TeacherProfile','reporting_to','id');
    }
    
    public function parentprofile()
    {
        return $this->hasMany('App\Models\ParentProfile','user_id','id');
    }

    public function scopeByBloodGroup($query , $blood_group)
    {
        $query->wherehas('userprofile',function ($query) use($blood_group)
            {
                $query->where('blood_group','=',$blood_group); 
            });
        return $query;
    }

    public function scopeByQualification($query , $qualification)
    {
        $query->wherehas('teacherprofile',function ($query) use($qualification)
            {
                $query->where('qualification_id','=',$qualification); 
            });
        return $query;
    }

    public function scopeByDesignation($query , $designation)
    {
        $query->wherehas('teacherprofile',function ($query) use($designation)
            {
                $query->where('designation','LIKE',$designation); 
            });
        return $query;
    }

    public function scopeByJopType($query , $job_type)
    {
        $query->wherehas('teacherprofile',function ($query) use($job_type)
            {
                $query->where('job_type',$job_type); 
            });
        return $query;
    }


    public function scopeByFirstNameParent($query , $firstname)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($firstname)
        {
            $query->whereHas('userParent', function ($q) use($firstname)
            {
                $q->whereHas('userprofile', function ($qu) use($firstname)
                {
                    $qu->where('firstname','LIKE',$firstname.'%');
                });
            }); 
        });
        return $query;
    }

    public function scopeByFullNameParent($query , $fullname)
    {
        /*$query->where('usergroup_id',6)->wherehas('parents', function ($query) use($fullname)
        {
            $query->whereHas('userParent', function ($q) use($fullname)
            {*/
                $query->whereHas('userprofile', function ($qu) use($fullname)
                {
                    $qu->where('firstname','LIKE',$fullname.'%');
                });
            /*}); 
        });*/
        return $q;
    }

    public function scopeByLastNameParent($query , $lastname)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($lastname)
        {
            $query->whereHas('userParent', function ($q) use($lastname)
            {
                $q->whereHas('userprofile', function ($qu) use($lastname)
                {
                    $qu->where('lastname','LIKE',$lastname.'%');
                });
            }); 
        });
        return $query;
    }

    public function scopeByMobileNoParent($query , $mobile_no)
    {
        /*$query->where('usergroup_id',6)->wherehas('parents', function ($query) use($mobile_no)
        {
            $query->whereHas('userParent', function ($q) use($mobile_no)
            {
                $q->where('mobile_no',$mobile_no);
            }); 
        });*/
        $query->where('mobile_no','LIKE',$mobile_no.'%');
        return $query;
    }

    public function scopeByEmailParent($query , $email)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($email)
        {
            $query->whereHas('userParent', function ($q) use($email)
            {
                $q->where('email',$email);
            }); 
        });
        return $query;
    }

    public function scopeByQualificationParent($query , $qualification)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($qualification)
        {
            $query->whereHas('userParent', function ($q) use($qualification)
            {
                $q->whereHas('parentprofile', function ($qu) use($qualification)
                {
                    $qu->where('qualification_id','=',$qualification); 
                });
            }); 
        });
        return $query;
    }

    public function scopeByOccupationParent($query , $occupation)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($occupation)
        {
            $query->whereHas('userParent', function ($q) use($occupation)
            {
                $q->whereHas('parentprofile', function ($qu) use($occupation)
                {
                    $qu->where('profession','LIKE',$occupation); 
                });
            }); 
        });
        return $query;
    }

    public function scopeByStandardLinkParent($query , $standardlink_id)
    {
        $query->where('usergroup_id',6)->wherehas('parents', function ($query) use($standardlink_id)
        {
            $query->whereHas('userParent', function ($quer) use($standardlink_id)
            {
                $quer->whereHas('children', function ($que) use($standardlink_id)
                {
                    $que->whereHas('userStudent', function ($qu) use($standardlink_id)
                    {
                        $qu->whereHas('studentAcademicLatest', function ($q) use($standardlink_id)
                        {
                            $q->where('standardlink_id',$standardlink_id);
                        });
                    });
                });
            }); 
        });
        return $query;
    }

    public function scopeByStandardLinkParentList($query , $standardlink_id)
    {
        $query->whereHas('children', function ($que) use($standardlink_id)
        {
            $que->whereHas('userStudent', function ($qu) use($standardlink_id)
            {
                $qu->whereHas('studentAcademicLatest', function ($q) use($standardlink_id)
                {
                    $q->where('standardlink_id',$standardlink_id);
                });
            });
        });
        return $query;
    }

    public function scopeByStudentNameParent($query , $student_name)
    {
        /*$query->where('usergroup_id',6)->wherehas('parents', function ($query) use($student_name)
        {
            $query->whereHas('userParent', function ($quer) use($student_name)
            {*/
                $query->whereHas('children', function ($que) use($student_name)
                {
                    $que->whereHas('userStudent', function ($qu) use($student_name)
                    {
                        $qu->whereHas('userprofile', function ($q) use($student_name)
                        {
                            $q->where('firstname','LIKE',$student_name.'%');
                        });
                    });
                });
           /* }); 
        });*/
        return $query;
    }

    public function scopeByBatch($query,$passing_session)
    {
        $query->wherehas('alumniprofile', function($query) use($passing_session)
        {
            $query->where('passing_session',$passing_session);
        });

        return $query;
    }

    public function getTeacherDetails()
    {
        $i = 0;
        $array = [];
        foreach ($this->teacherprofile as $teacher) 
        { 
            $array['designation']                               = $teacher->designation;
            $array['designation_name']                          = str_replace('_',' ', ucwords($teacher->designation));
            $array['reporting_to']                              = $teacher->reporting_to;
            $array['sub_designation']                           = $teacher->sub_designation;
            $array['employee_id']                               = $teacher->employee_id;
            $array['ug_degree']                                 = $teacher->ug_degree;
            $array['pg_degree']                                 = $teacher->pg_degree;
            $array['specialization']                            = $teacher->specialization;
            $array['job_type']                                  = $teacher->job_type;
            $array['interested_in']                             = $teacher->interested_in;
            $array['sub_qualification']                         = $teacher->sub_qualification;
            $array['status']                                    = $teacher->status;
            $array['qualification_id'][$i]['qualification_id']  = $teacher->qualification_id;
            $array['qualification_name'][$i]                    = $teacher->qualification->display_name;
            $i++;
        }

        return $array;
    }

    public function getParentDetails()
    {
        $i = 0;
        $array = [];
        foreach ($this->parentprofile as $parent) 
        { 
            $array['profession']                                = $parent->profession;
            $array['sub_occupation']                            = $parent->sub_occupation;
            $array['designation']                               = $parent->designation;
            $array['organization_name']                         = $parent->organization_name;
            $array['official_address']                          = $parent->official_address;
            $array['relation']                                  = $parent->relation;
            $array['annual_income']                             = $parent->annual_income;
            $array['qualification_id'][$i]['qualification_id']  = $parent->qualification_id;
            $array['qualification_name'][$i]                    = $parent->qualification->display_name;
            $i++;
        }

        return $array;
    }

    public function getChildren()
    {
        $i = 0;
        $array = [];
        foreach ($this->children as $children) 
        { 
            $data[] = $children->userStudent->FullName.' ('.$children->userStudent->studentAcademicLatest->standardLink->StandardSection.')';
            $i++;
        }
        $child  = implode(', ',$data);

        return $child;
    }

    public function getAlumniEducationDetails()
    {
        $array = [];
        if (is_array($this->alumniprofile['institution_name'])) //new condition
        {
            for($i = 0 ; $i < count($this->alumniprofile['institution_name']) ; $i++)
            {
                $array[$i][]    = $this->alumniprofile['institution_name'][$i];
                $array[$i][]    = $this->alumniprofile['degree'][$i];
                $array[$i][]    = $this->alumniprofile['specialization'][$i];
                $array[$i][]    = $this->alumniprofile['college_start_year'][$i] == null ? null:$this->alumniprofile['college_start_year'][$i];
                $array[$i][]    = $this->alumniprofile['college_end_year'][$i] == null ? null:$this->alumniprofile['college_end_year'][$i];
                $array[$i][]    = $this->alumniprofile['grade'][$i];
            }
        }
        return $array;
    }

    public function getAlumniEducation()
    {
        $array = [];
        if (is_array($this->alumniprofile['institution_name'])) //new condition
        {
            for($i = 0 ; $i < count($this->alumniprofile['institution_name']) ; $i++)
            {
                $array['inputs'][$i]['institution_name']    = $this->alumniprofile['institution_name'][$i];
                $array['inputs'][$i]['degree']              = $this->alumniprofile['degree'][$i];
                $array['inputs'][$i]['specialization']      = $this->alumniprofile['specialization'][$i];
                $array['inputs'][$i]['college_start_year']  = $this->alumniprofile['college_start_year'][$i] == null ? null:$this->alumniprofile['college_start_year'][$i];

                if( ( $this->alumniprofile['college_end_year'][$i] == null ) && ( $this->alumniprofile['grade'][$i] == null ) )
                {
                    $array['inputs'][$i]['current_studying'] = 1;
                }
                else
                {
                    $array['inputs'][$i]['college_end_year']    = $this->alumniprofile['college_end_year'][$i] == null ? null:$this->alumniprofile['college_end_year'][$i];
                    $array['inputs'][$i]['grade']               = $this->alumniprofile['grade'][$i];
                }
            }
        }
        return $array;
    }

    public function getAlumniWorkDetails()
    {
        $array = [];
        if (is_array($this->alumniprofile['company_name'])) //new condition
        {
    
            for($i=0;$i<count($this->alumniprofile['company_name']);$i++)
            {
                $array[$i][]   = $this->alumniprofile['company_name'][$i] == null ? null:$this->alumniprofile['company_name'][$i];
                $array[$i][]   = $this->alumniprofile['designation'][$i] == null ? null:$this->alumniprofile['designation'][$i];
                $array[$i][]   = $this->alumniprofile['location'][$i] == null ? null:$this->alumniprofile['location'][$i];
                $array[$i][]   = $this->alumniprofile['job_start_year'][$i] == null ? null:$this->alumniprofile['job_start_year'][$i];
                $array[$i][]   = $this->alumniprofile['job_start_month'][$i] == null ? null:$this->alumniprofile['job_start_month'][$i];
                $array[$i][]   = $this->alumniprofile['job_end_year'][$i] == null ? null:$this->alumniprofile['job_end_year'][$i];
                $array[$i][]   = $this->alumniprofile['job_end_month'][$i] == null ? null:$this->alumniprofile['job_end_month'][$i];
            }
        }
        return $array;
    }

    public function getAlumniWork()
    {
        $array = [];
        if (is_array($this->alumniprofile['company_name'])) //new condition
        {
    
            for($i=0;$i<count($this->alumniprofile['company_name']);$i++)
            {
                $array['inputs'][$i]['company_name']    = $this->alumniprofile['company_name'][$i] == null ? null:$this->alumniprofile['company_name'][$i];
                $array['inputs'][$i]['designation']     = $this->alumniprofile['designation'][$i] == null ? null:$this->alumniprofile['designation'][$i];
                $array['inputs'][$i]['location']        = $this->alumniprofile['location'][$i] == null ? null:$this->alumniprofile['location'][$i];
                $array['inputs'][$i]['job_start_year']  = $this->alumniprofile['job_start_year'][$i] == null ? null:$this->alumniprofile['job_start_year'][$i];
                $array['inputs'][$i]['job_start_month'] = $this->alumniprofile['job_start_month'][$i] == null ? null:$this->alumniprofile['job_start_month'][$i];

                if( ($this->alumniprofile['job_end_year'][$i] == null) && ($this->alumniprofile['job_end_month'][$i] == null) )
                {
                    $array['inputs'][$i]['present'] = 1;
                }
                else
                {
                    $array['inputs'][$i]['job_end_year']    = $this->alumniprofile['job_end_year'][$i] == null ? null:$this->alumniprofile['job_end_year'][$i];
                    $array['inputs'][$i]['job_end_month']   = $this->alumniprofile['job_end_month'][$i] == null ? null:$this->alumniprofile['job_end_month'][$i];
                }
            }
        }
        return $array;
    }

    public function promotion()
    {
        return $this->hasMany('App\Models\Promotion','user_id','id');
    }

    public function disciplineUser()
    {
        return $this->hasMany('App\Models\Discipline','user_id','id');
    }

    public function disciplineTeacher()
    {
        return $this->hasMany('App\Models\Discipline','reported_by','id');
    }

    public function getFullNameAttribute()
    {
        return $this->userprofile->firstname.' '.$this->userprofile->lastname;
    }

    public function standardLink()
    {
        return $this->hasOne('App\Models\StandardLink','class_teacher_id','id');
    }

    public function teacherlink()
    {
        return $this->hasMany('\App\Models\Teacherlink','teacher_id','id');
    }

    public function teacherlinkCurrentAcademicYear()
    {
        $academic_year = SiteHelper::getAcademicYear($this->school_id);
        return $this->hasMany('\App\Models\Teacherlink','teacher_id','id')->where('academic_year_id',$academic_year->id);
    }

    public function attendanceUser()
    {
        return $this->hasMany('\App\Models\Attendance','user_id','id');
    }

    public function getAttendanceUserAbsentAttribute()
    {
        $academic_year = SiteHelper::getAcademicYear($this->school_id);
        return $this->attendanceUser->where('academic_year_id',$academic_year->id)->where('status',0);
    }

    public function attendanceAdmin()
    {
        return $this->hasMany('\App\Models\Attendance','recorded_by','id');
    }

    public function booklending()
    {
        return $this->belongsTo('\App\Models\BookLending','user_id');
    }

    public function lending()
    {
        return $this->hasMany('\App\Models\BookLending','user_id','id');
    }

    public function assignment()
    {
        return $this->hasMany('\App\Models\Assignment','teacher_id','id');
    }

    public function studentAssignment()
    {
        return $this->hasMany('\App\Models\StudentAssignment','user_id','id');
    }

    public function teacherAssignment()
    {
        return $this->hasMany('\App\Models\StudentAssignment','marks_given_by','id');
    }

    public function requestedUser()
    {
        return $this->hasMany('\App\Models\TeacherLeaveApplication','user_id','id');
    }

    public function approvedUser()
    {
        return $this->hasMany('\App\Models\TeacherLeaveApplication','approved_by','id');
    }

    public function feePayment()
    {
        if(class_exists('Gegok12\Fee\Models\FeePayment'))
        {
            return $this->hasMany('\Gegok12\Fee\Models\FeePayment','user_id','id');
        }
        else{
            return $this->hasMany('\App\Models\FeePayment','user_id','id');
        }
    }

    public function getSchoolLogoAttribute()
    {
        return $this->school->schoolDetailLogo;
    }

    public function getSchoolLogoPathAttribute()
    {
        return $this->school->schoolDetailLogo->LogoPath;
    }

    public function messages()
    {
        return $this->hasMany('\App\Models\Chat');
    }


    public function inConversation($id)
    {
        return $this->conversations->contains('id',$id);
    }

    public function hasRead(Conversation $conversation)
    {
        return $this->conversations->find($conversation->id)->pivot->read_at;
        
    }

    public function present()
    {
        return new UserPresenter($this);
    }

    public function conversations()
    {
        return $this->belongsToMany('App\Models\Conversation')->withPivot('read_at');
    }
    
    public function getLeaveCountAttribute()
    {
        return $this->requestedUser->where('status','approved')->count();
    }

    public function document()
    {
        return $this->hasMany('\App\Models\Document','user_id','id');
    }

    public function authentication()
    {
        return $this->hasMany('\App\Models\Authentication','user_id','id');
    }

    public function task()
    {
        return $this->belongsTo('\App\Models\Task','user_id');
    }

    public function approvedLessonPlan()
    {
        return $this->hasMany('\App\Models\LessonPlanApproval','approved_by','id');
    }

    public function postDetail()
    {
        return $this->hasMany('\App\Models\PostDetail','user_id','id');
    }

    public function postComment()
    {
        return $this->hasMany('\App\Models\PostComment','user_id','id');
    }

    public function postCommentDetail()
    {
        return $this->hasMany('\App\Models\PostCommentDetail','user_id','id');
    }

    public function visitorlog()
    {
        return $this->belongsTo('\App\Models\VisitorLog','employee_id');
    }

    public function visitor()
    {
        return $this->belongsTo('\App\Models\VisitorLog','student_id');
    }


    public function bankdetail()
    {
        return $this->hasOne('App\Models\TransactionAccount','user_id','id');
    }

    public function payrolls()
    {
        return $this->hasMany('App\Models\Payroll','staff_id','id');
    }

    public function payrolltransactions()
    {
        return $this->hasMany('App\Models\PayrollTransaction','staff_id','id');
    }

    public function salarydetail()
    {
        $array=[];
        if(count($this->payrolltransactions)!=0 )
        {
          
          $array['salaryadvance']=$this->payrolltransactions()->where('paytype_id',2)->sum('amount');
           $array['returnadvance']=$this->payrolltransactions()->where('paytype_id',3)->sum('amount');
           $array['pending']=$array['salaryadvance']-$array['returnadvance'];
            return $array;

        }
        return 0;
    }

    public static function scopeByDriver($query,$usergroup_id)
    {
        return $query->whereHas(
            'roles', function($q){
                $q->where('name', 'transport_driver');
            }
        );
    }


    public function routeStudent()
    {
        return $this->hasOne('App\Models\RouteStudent','user_id','id');
    }

    public function routeNotificationForFcm($notification)
    {

        //return $notification->data['token'];
        return $notification->token;
       // return $this->userdevicetoken()->pluck('token')->toArray();
    }
    public function scopeByActive($query)
    {
        $query->where('status','active'); 

        return $query;
    }
}