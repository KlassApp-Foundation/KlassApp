<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    //

    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'states';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id' , 'name' , 'status'
    ];

    public function country()
    {
        return $this->belongsTo('App\Models\Country','country_id');
    }

    public function school()
    {
        return $this->hasMany('App\Models\School','state_id','id');
    }
    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','state_id','id');
    }

    public function city()
    {
        return $this->hasMany('App\Models\City','state_id','id');
    }
}
