<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Message;
class Conversation extends Model
{

	protected $fillable = [
		'last_message_at',
         'uuid'

	];

	protected $dates = [
		'last_message_at'


	];

	public function getRouteKeyName()
	{
		return 'uuid';
	}

    public function users()
    {
        return $this->belongsToMany('App\Models\User')->withPivot('read_at')
            ->withTimestamps()
        	->oldest();

           
    }

     public function others()
    {
        return $this->users()->where('user_id','!=',auth()->id());
    }

    public function messages()
    {
    	return $this->hasMany('App\Models\Message')->latest();
    }
}
