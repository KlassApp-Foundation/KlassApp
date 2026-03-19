<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'qualifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'display_name', 'type' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function parentprofile()
    {
        return $this->hasMany('App\Models\ParentProfile','qualification_id','id');
    }

    public function teacherprofile()
    {
        return $this->hasMany('App\Models\TeacherProfile','qualification_id','id');
    }

    public function ugDegree()
    {
        return $this->hasMany('App\Models\TeacherProfile','ug_degree','id');
    }

    public function pgDegree()
    {
        return $this->hasMany('App\Models\TeacherProfile','pg_degree','id');
    }
}
