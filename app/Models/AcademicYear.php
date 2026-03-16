<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'academic_years';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'name' , 'description' , 'start_date' , 'end_date' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    
    //protected $dates = ['start_date','end_date'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function getStatusDisplayAttribute()
    {
        if($this->status == 1)
        {
            $status = 'Current Academic Year';
        }
        elseif($this->status == 2)
        {
            $status = 'New Academic Year';
        }
        elseif($this->status == 0)
        {
            $status = 'Old Academic Year';
        }

        return $status;
    }

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function standard()
    {
        return $this->hasMany('App\Models\Standard','academic_year_id','id');
    }

    public function studentAcademic()
    {
        return $this->hasMany('App\Models\StudentAcademic','academic_year_id','id');
    }

    public function marks()
    {
        return $this->hasMany('App\Models\Mark','academic_year_id','id');
    }

    public function teacherprofile()
    {
        return $this->hasMany('App\Models\TeacherProfile','academic_year_id','id');
    }

    public function currentPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','current_academic_year_id','id');
    }

    public function nextPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','next_academic_year_id','id');
    }

    public function discipline()
    {
        return $this->hasMany('App\Models\Discipline','academic_year_id','id');
    }

    public function bulletin()
    {
        return $this->hasMany('App\Models\Bulletin','academic_year_id','id');
    }

    public function attendance()
    {
        return $this->hasMany('\App\Models\Attendance','academic_year_id','id');
    }

    public function assignment()
    {
        return $this->hasMany('\App\Models\Assignment','academic_year_id','id');
    }

    public function subject()
    {
        return $this->hasMany('\App\Models\Subject','academic_year_id','id');
    }

    public function leaveType()
    {
        return $this->hasMany('\App\Models\LeaveType','academic_year_id','id');
    }

    public function teacherLeaveApplication()
    {
        return $this->hasMany('\App\Models\TeacherLeaveApplication','academic_year_id','id');
    }

    public function fee()
    {
        return $this->hasMany('\App\Models\Fee','academic_year_id','id');
    }

    public function sendMail()
    {
        return $this->hasMany('App\Models\SendMail','academic_year_id','id');
    }  
}