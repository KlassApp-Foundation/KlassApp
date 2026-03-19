<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LessonPlanApproval extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lesson_plan_approvals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lesson_plan_id' , 'comments' , 'approved_by' , 'approved_at'  
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['approved_at','deleted_at'];

    public function lessonPlan()
    {
        return $this->belongsTo('\App\Models\LessonPlan','lesson_plan_id');
    }

    public function user()
    {
        return $this->belongsTo('\App\Models\User','approved_by');
    }
}
