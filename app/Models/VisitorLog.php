<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudentAcademic;

class VisitorLog extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'visitor_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'school_id' , 'academic_year_id' , 'email' , 'name' , 'relation' , 'company_name' , 'contact_number' , 'address' , 'student_id' , 'relation_with_student' , 'relation_name' , 'number_of_visitors' , 'visiting_purpose' , 'employee_id' , 'date_of_visit' , 'entry_time' , 'exit_time' , 'remark' 
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at' , 'date_of_visit'];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function student()
    {
        return $this->belongsTo('\App\Models\User','student_id');
    }

    public function employee()
    {
      return $this->belongsTo('\App\Models\User','employee_id');
    }

    public function getstandard($student_id)
    {
        return $this->student->studentAcademicLatest->standardLink_id;
    }
}