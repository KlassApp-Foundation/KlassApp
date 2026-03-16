<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' , 'title' , 'type', 'task_date' , 'reminder' , 'reminder_date' , 'to_do_list' , 'task_status' , 'task_flag'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['task_date' , 'reminder_date'];

    protected $casts = [
        'task_date' => 'datetime',
        'reminder_date' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function user()
    {
    	return $this->hasMany('\App\Models\User','id','user_id');
    }

    public function getTodayTaskAttribute()
    {
        return Task::where('task_flag',1)->where('task_status',0)->count();
    }

    public function getOverDueTaskAttribute()
    {
        return Task::where('task_flag',0)->where('task_status',0)->count();
    }

    public function getUpcomingTaskAttribute()
    {
        return Task::where('task_flag',2)->where('task_status',0)->count();
    } 

    public function taskAssignee()
    {
        return $this->hasMany('\App\Models\TaskAssignee','task_id','id');
    }

    public function getReminderValueAttribute()
    {
        $task_date = date('Y-m-d H:i:s',strtotime($this->task_date));
        if($this->reminder == 'one_hour_before_the_task')
        {
            $reminder_date = Carbon::parse($task_date)->subHours(1)->format('Y-m-d H:i:s');
        }
        elseif($this->reminder == 'one_day_before_the_task')
        {
            $reminder_date = Carbon::parse($task_date)->subDays(1)->format('Y-m-d H:i:s');
        }
        elseif($this->reminder == 'two_days_before_the_task')
        {
            $reminder_date = Carbon::parse($task_date)->subDays(2)->format('Y-m-d H:i:s');
        }

        return $reminder_date;
    }

    public function scopeByStatus($query,$status)
    {
        $query->where('task_status',$status);

        return $query;
    }

    public function scopeByType($query,$type,$user_id)
    {
        if($type == 'by_me')
        {
            $query->where('user_id',$user_id);
        }
        else
        {
            $query->whereHas('taskAssignee', function ($q) use($user_id){
                $q->where('user_id',$user_id);
            });
        }

        return $query;
    }

    public function getFlagAttribute()
    {
        if($this->task_flag == 0)
        {
            $task_flag = 'Overdue';
        }
        elseif($this->task_flag == 1)
        {
            $task_flag = 'Today';
        }
        else
        {
            $task_flag = 'Upcoming';
        }

        return $task_flag;
    }
}