<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Conversation;
use App\Models\User;
class Message extends Model
{

	protected $fillable = [

	'user_id',
	'body'

	];

	public function isOwn()
	{
		return $this->user_id === auth()->id();
	}
    public function conversation()
    {
    	return $this->belongsTo('App\Models\Conversation');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }
}
