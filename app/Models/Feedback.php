<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    //
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id', 'parent_id' , 'student_id' , 'admin_id' , 'status'    
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function feedbackMessage()
    {
        return $this->hasMany('App\Models\FeedbackMessage','feedback_id','id');
    }

    public function latestMessage()
    {
        return $this->hasOne('App\Models\FeedbackMessage','feedback_id','id')->orderByDesc('id')->limit(1);
    }

    public function parent() 
    {
        return $this->belongsTo('App\Models\User', 'parent_id');
    }

    public function student() 
    {
        return $this->belongsTo('App\Models\User', 'student_id');
    }

    public function admin() 
    {
        return $this->belongsTo('App\Models\User', 'admin_id');
    }
}