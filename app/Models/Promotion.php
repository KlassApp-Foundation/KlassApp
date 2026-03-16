<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'user_id' , 'current_academic_year_id' ,'current_standard_id', 'current_section_id', 'exam_id','next_academic_year_id','next_standard_id', 'next_section_id' , 'comments' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function currentAcademicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear','current_academic_year_id');
    }

    public function nextAcademicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear','next_academic_year_id');
    }

    public function currentStandard()
    {
        return $this->belongsTo('App\Models\Standard','current_standard_id');
    }

    public function nextStandard()
    {
        return $this->belongsTo('App\Models\Standard','next_standard_id');
    }

    public function currentSection()
    {
        return $this->belongsTo('App\Models\Section','current_section_id');
    }

    public function nextSection()
    {
        return $this->belongsTo('App\Models\Section','next_section_id');
    }
}
