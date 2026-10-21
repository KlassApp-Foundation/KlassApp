<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use App\Models\Concerns\HasApprovals;
use App\Models\Contracts\Approvable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LessonPlan extends Model implements Approvable
{
    use SoftDeletes;
    use HasApprovals;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lesson_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'teacher_link_id' , 'unit_no' , 'unit_name' , 'description' , 'title' , 'duration' , 'objective' , 'materials_required' , 'introduction' , 'procedure' , 'conclusion' , 'notes' , 'assessment' , 'modification' , 'status'    
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function lessonPlanApproval()
    {
        return $this->hasOne('\App\Models\LessonPlanApproval','lesson_plan_id','id');
    }

    public function teacherlink()
    {
        return $this->belongsTo('\App\Models\Teacherlink','teacher_link_id');
    }
}
