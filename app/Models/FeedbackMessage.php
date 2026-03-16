<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;
use Illuminate\Database\Eloquent\Model;

class FeedbackMessage extends Model
{
    //
    use PresentableTrait;
    use SoftDeletes;

    protected $presenter = "App\Presenters\UserPresenter";

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback_messages';


    protected $appends = array('message');

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'user_id' , 'feedback_id' , 'message' , 'file' , 'category' , 'is_seen' , 'deleted_from_sender' , 'deleted_from_receiver'    
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

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function feedback()
    {
        return $this->belongsTo('App\Models\Feedback','feedback_id');
    }

    public function getMessageAttribute($message)
    {
        return \Purify::clean($message);
    }
}