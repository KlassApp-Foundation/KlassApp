<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TaskAssignee extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_assignees';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'task_id' , 'user_id' , 'standardLink_id' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function user()
    {
    	return $this->belongsTo('\App\Models\User','user_id');
    }

    public function task()
    {
    	return $this->belongsTo('\App\Models\Task','task_id');
    }

    public function standardLink()
    {
    	return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }
}