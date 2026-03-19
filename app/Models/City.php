<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    //

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id' , 'state_id' , 'name' , 'status'
    ];

    public function country()
    {
        return $this->belongsTo('App\Models\Country','country_id');
    }

    public function state()
    {
        return $this->belongsTo('App\Models\State','state_id');
    }

    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','city_id','id');
    }

    public function school()
    {
        return $this->hasMany('App\Models\School','city_id','id');
    }
}
