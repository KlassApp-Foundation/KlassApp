<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
class Assignment extends Model
{
    //
    use Common;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'standardLink_id' , 'subject_id' , 'teacher_id' ,'title' , 'description' , 'attachment' , 'marks' , 'assigned_date' , 'submission_date' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['assigned_date' , 'submission_date'];

    protected $casts = [
        'assigned_date' => 'datetime',
        'submission_date' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
    	return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
    	return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }

    public function subject()
    {
    	return $this->belongsTo('\App\Models\Subject','subject_id');
    }

    public function teacher()
    {
    	return $this->belongsTo('\App\Models\User','teacher_id');
    }

    public function studentAssignment()
    {
        return $this->hasOne('\App\Models\StudentAssignment','assignment_id','id');
    }

    public function assignmentApproval()
    {
        return $this->hasOne('\App\Models\AssignmentApproval','assignment_id','id');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachment);
    }

    public function viewers()
    {
        return $this->morphMany(StudentHistory::class, 'entity');
    }
}