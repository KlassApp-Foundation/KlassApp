<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'reminders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'school_id' , 'from' , 'to' , 'subject' , 'message' , 'entity_id' , 'entity_name' , 'via' , 'queue_status' , 'sms_response' , 'executed_at' , 'template_id' , 'data'
	];

	protected $casts = ['data'=>'array' , 'sms_response'=>'array'];
    
	public function events()
   	{
   		return $this->belongsTo('App\Models\Events','entity_id');
   	}

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function user()
   	{
   		return $this->belongsTo('App\Models\User','to','email');
   	}

   	public function userSms()
   	{
   		return $this->belongsTo('App\Models\User','to','mobile_no');
   	}
}