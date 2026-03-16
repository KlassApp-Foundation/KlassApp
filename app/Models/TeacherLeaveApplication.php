<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherLeaveApplication extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teacher_leave_applications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' , 'from_date' , 'to_date' , 'reason_id' , 'remarks' ,'leave_type_id' , 'approved_by' , 'approved_on' , 'comments' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['from_date' ,'to_date', 'deleted_at' , 'approved_on'];

    protected $casts = [
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('\App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function teacher()
    {
        return $this->belongsTo('\App\Models\User','user_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }

    public function absentReason()
    {
        return $this->belongsTo('App\Models\AbsentReason','reason_id');
    }

    public function leaveType()
    {
        return $this->belongsTo('\App\Models\LeaveType','leave_type_id');
    }

    public function approvedUser()
    {
        return $this->belongsTo('\App\Models\User','approved_by');
    }

    public function scopeByDate($query,$from_date,$to_date)
    {
       $query->where(function ($q) use ($from_date, $to_date) {
            $q->where('from_date', '>=', $from_date)
               ->where('from_date', '<', $to_date);

        })->orWhere(function ($q) use ($from_date, $to_date) {
            $q->where('from_date', '<=', $from_date)
               ->where('to_date', '>', $to_date);

        })->orWhere(function ($q) use ($from_date, $to_date) {
            $q->where('to_date', '>', $from_date)
               ->where('to_date', '<=', $to_date);

        })->orWhere(function ($q) use ($from_date, $to_date) {
            $q->where('from_date', '>=', $from_date)
               ->where('to_date', '<=', $to_date);

        });

        return $query;
    }
}