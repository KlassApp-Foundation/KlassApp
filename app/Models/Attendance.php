<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attendances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'standardLink_id' ,'user_id', 'date', 'session','status','reason_id', 'remarks' , 'recorded_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = [ 'date' , 'deleted_at'];

    protected $casts = [
        'date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('App\Models\StandardLink','standardLink_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function absentReason()
    {
        return $this->belongsTo('App\Models\AbsentReason','reason_id');
    }

    public function admin()
    {
        return $this->belongsTo('App\Models\User','recorded_by')->where('usergroup_id',3);
    }

    public function scopeByRole($query,$usergroup_id)
   {
       $query->wherehas('user',function ($query) use($usergroup_id)
            {
                $query->where('usergroup_id',$usergroup_id); 
            });
        return $query;
   }
}
