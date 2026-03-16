<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class SchoolDetail extends Model
{
	use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table='school_details';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
	    'school_id','meta_key','meta_value',
	];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

	public function getLogoPathAttribute()
    {
    	if($this->meta_key=='school_logo')
    	{
          return $this->getFilePath($this->meta_value);
        }
    }
}