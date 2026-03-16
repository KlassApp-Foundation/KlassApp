<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table='subjects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [

	    'school_id' , 'academic_year_id' , 'standard_id' , 'section_id' , 'name' , 'code' , 'type' 
	];

    public function getNameAttribute($value)
    {
        return strtoupper($value);
    }

    public function school()
    {
        return $this->belongsTo('\App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standard()
    {
        return $this->belongsTo('\App\Models\Standard','standard_id');
    }

    public function section()
    {
        return $this->belongsTo('\App\Models\Section','section_id');
    }

	public function teacherlink()
    {
        return $this->hasMany('\App\Models\Teacherlink','subject_id','id');
    }

    public function mark()
    {
        return $this->hasMany('App\Models\Mark','subject_id','id');
    }

    public function schedule()
    {
        return $this->hasMany('App\Models\ExamSchedule','subject_id','id');
    }
}