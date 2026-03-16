<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    //
    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'schools';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name' , 'email' , 'phone' , 'address' , 'country_id' , 'state_id' , 'city_id' , 'pincode' , 'slug' , 'status'
    ];

    public function country()
    {
      return $this->belongsTo('App\Models\Country','country_id');
    }

    public function state()
    {
      return $this->belongsTo('App\Models\State','state_id');
    } 

    public function city()
    {
      return $this->belongsTo('App\Models\City','city_id');
    }

    public function schoolDetail()
    {
        return $this->hasMany('App\Models\SchoolDetail','school_id','id');
    }

    public function schoolDetailBoard()
    {
        return $this->hasOne('App\Models\SchoolDetail','school_id','id')->where('meta_key','board');
    }

    public function schoolDetailLogo()
    {
        return $this->hasOne('App\Models\SchoolDetail','school_id','id')->where('meta_key','school_logo');
    }

    public function academicYear()
    {
    	return $this->hasMany('App\Models\AcademicYear','school_id','id');
    }

    public function studentAcademic()
    {
        return $this->hasMany('App\Models\StudentAcademic','school_id','id');
    }

    public function section()
    {
        return $this->hasMany('App\Models\Section','school_id','id');
    }

    public function standard()
    {
        return $this->hasMany('App\Models\Standard','school_id','id');
    }

    public function user()
    {
        return $this->hasMany('App\Models\User','school_id','id');
    }

    public function teacher()
    {
        return $this->hasMany('App\Models\User','school_id','id')->where('usergroup_id',5);
    }

    public function admin()
    {
        return $this->hasMany('App\Models\User','school_id','id')->where('usergroup_id',3);
    }

    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','school_id','id');
    }

    public function event()
    {
        return $this->hasMany('App\Models\Event');
    }

    public function subscription()
    {
        return $this->hasMany('App\Models\Subscription','school_id','id');
    }

     public function reminder()
    {
        return $this->hasMany('App\Models\Reminder','school_id','id');
    }

    public function fulladdress()
    {
        $fulladdress =  $this->address
                        . "<br/>". $this->city
                        . "<br />" .$this->pincode 
                        . "<br /> ". $this->state
                        . "<br /> ". "India" ;

        return $fulladdress;

    }

    public function bulletin()
    {
        return $this->hasMany('App\Models\Bulletin','school_id','id');
    }

    public function sendMail()
    {
        return $this->hasMany('App\Models\SendMail','school_id','id');
    }  

    public function scopeIsActive($query,$school_id)
    {
        $query->where(function ($query) use($school_id)
        {
            $query ->where('id',$school_id)->where('status',1); 
        });
        return $query;
    } 

    public function getActiveMembersAttribute()
    {
        return $this->hasMany('App\Models\User','school_id','id')->with('user')->where('usergroup_id',5)->whereHas('userprofile', function ($query)
            {
                $query->where('status','active');
            })->count(); 
    }

    public function teacherprofile()
    {
        return $this->hasMany('App\Models\TeacherProfile','school_id','id');
    }

    public function parentprofile()
    {
        return $this->hasMany('App\Models\ParentProfile','school_id','id');
    }

    public function promotion()
    {
        return $this->hasMany('App\Models\Promotion','school_id','id');
    }

    public function discipline()
    {
        return $this->hasMany('App\Models\Discipline','school_id','id');
    }

    public function attendance()
    {
        return $this->hasMany('\App\Models\Attendance','school_id','id');
    }

    public function timetable()
    {
        return $this->hasMany('\App\Models\Timetable','school_id','id');
    }

    public function assignment()
    {
        return $this->hasMany('\App\Models\Assignment','school_id','id');
    }

    public function subject()
    {
        return $this->hasMany('\App\Models\Subject','school_id','id');
    }

    public function leaveType()
    {
        return $this->hasMany('\App\Models\LeaveType','school_id','id');
    }

    public function teacherLeaveApplication()
    {
        return $this->hasMany('\App\Models\TeacherLeaveApplication','school_id','id');
    }

    public function fee()
    {
        return $this->hasMany('\App\Models\Fee','school_id','id');
    }

    public function document()
    {
        return $this->hasMany('\App\Models\Document','school_id','id');
    }

    public function getDetails()
    {
        $array = [];

        $array['admin']     = $this->admin;
        $array['student']   = $this->user->where('usergroup_id',6)->take(3);
        $array['parent']    = $this->user->where('usergroup_id',7)->take(3);
        $array['librarian'] = $this->user->where('usergroup_id',8)->take(1);
        $array['receptionist'] = $this->user->where('usergroup_id',10)->take(1);
        $array['accountant'] = $this->user->where('usergroup_id',11)->take(1);

        return $array;
    }
}