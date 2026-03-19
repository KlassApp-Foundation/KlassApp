<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    //

    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'countries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name' , 'short_name' , 'iso_code' ,'tel_prefix', 'status', 'order'
    ];

    public function school()
    {
        return $this->hasMany('App\Models\School','country_id','id');
    }

    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','country_id','id');
    }

    public function state()
    {
        return $this->hasMany('App\Models\State','country_id','id');
    }

    public function city()
    {
        return $this->hasMany('App\Models\City','country_id','id');
    }
}
