<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherProfile extends Model
{
    //
    use SoftDeletes;
    use Common;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teacherprofile';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' , 'qualification_id' , 'ug_degree' , 'pg_degree' , 'sub_qualification', 'specialization' , 'designation' , 'sub_designation' , 'employee_id' , 'reporting_to' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }
     public function userprofile()
    {
        return $this->belongsTo('App\Models\Userprofile','user_id','user_id');
    }

    public function academicYear()
    {
    	return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function user()
    {
        return $this->belongsTo('\App\Models\User','user_id');
    }

    public function reportingTo()
    {
    	return $this->belongsTo('\App\Models\User','reporting_to');
    }

    public function qualification()
    {
        return $this->belongsTo('\App\Models\Qualification','qualification_id');
    }

    public function ugDegree()
    {
        return $this->belongsTo('\App\Models\Qualification','ug_degree');
    }

    public function pgDegree()
    {
    	return $this->belongsTo('\App\Models\Qualification','pg_degree');
    }

    public function getAvatarPathAttribute()
    {
      return $this->getFilePath($this->avatar);
    }
}