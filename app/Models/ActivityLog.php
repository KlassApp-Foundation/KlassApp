<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    //

   protected $table = 'activity_log';

    protected $fillable = [
        'log_name', 'description', 'subject_id', 'subject_type', 'causer_id', 'causer_type', 'properties','batch_uuid'
    ];
   protected $casts=[
    	'properties'=>'array'	
    ];

   protected $with = array('user');


   public function user()
   {
       return $this->belongsTo('App\Models\User', 'causer_id');
	}
}
