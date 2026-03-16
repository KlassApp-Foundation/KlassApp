<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
//use Laravel\Scout\Searchable;

class LessonPlan extends Model
{
    //
    use SoftDeletes;
    //use Searchable;

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
        'teacher_link_id' , 'unit_no' , 'unit_name' , 'description' , 'title' , 'duration' , 'objective' , 'materials_required' , 'introduction' , 'procedure' , 'conclusion' , 'notes' , 'assessment' , 'modification' , 'status'    
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'lesson_plans';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...
        //return array('id' => $array['id'], 'teacher_link_id' => $array['teacher_link_id'], 'title' => $array['title'], 'unit_name' => $array['unit_name']);
        return $array;
    }

    public function lessonPlanApproval()
    {
        return $this->hasOne('\App\Models\LessonPlanApproval','lesson_plan_id','id');
    }

    public function teacherlink()
    {
        return $this->belongsTo('\App\Models\Teacherlink','teacher_link_id');
    }
}
