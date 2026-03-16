<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
class EventGallery extends Model
{
	use Common;
    protected $table='event_galleries';

     protected $fillable=['school_id','event_id','path','created_by','updated_by'];



    public function getFullPathAttribute()
    {
    	return $this->getFilePath($this->path);
    }

     public function event()
    {
        return $this->belongsTo('App\Models\Events','event_id');
    }

    
}
