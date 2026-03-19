<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Userprofile extends Model
{
    use PresentableTrait;
    protected $presenter = "App\Presenters\UserprofilePresenter";
    use SoftDeletes;
    use Common;
    use HasFactory;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_of_birth','joining_date','deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userprofiles';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
      'school_id' , 'user_id' , 'usergroup_id' , 'firstname' , 'lastname'  , 'alternate_no' , 'gender' , 'date_of_birth' , 'blood_group' , 'birth_place' , 'native_place' , 'mother_tongue' , 'caste' , 'address' , 'city_id' , 'state_id' , 'country_id' , 'pincode' , 'relation' , 'aadhar_number' , 'registration_number' , 'EMIS_number' , 'joining_date' , 'notes' , 'avatar' , 'marital_status' , 'status'
    ];


    public function getFirstNameAttribute($value)
    {
        return strtoupper($value);
    }

    public function getLastNameAttribute($value)
    {
        return strtoupper((string)$value);
    }

    public function school()
    {
      return $this->belongsTo('App\Models\School','school_id');
    }

    public function user()
    {
      return $this->belongsTo('App\Models\User','user_id');
    }

    public function usergroup()
    {
      return $this->belongsTo('App\Models\Usergroup','usergroup_id');
    }

    public function qualification()
    {
      return $this->belongsTo('\App\Models\Qualification','qualification_id');
    }

    public function country()
    {
      return $this->belongsTo('App\Models\Country','country_id');
    }

    public function state()
    {
      return $this->belongsTo('App\Models\State','state_id');
    } 

    public function city()
    {
      return $this->belongsTo('App\Models\City','city_id');
    }    

    public function scopeBySchool($query,$school_id)
    {
      $query->where('school_id',$school_id);
      return $query;
    }

    public function scopeByRole($query,$usergroup_id)
    {
      $query->where('usergroup_id',$usergroup_id);
      return $query;
    }

    public function getAvatarPathAttribute()
    {
      return $this->getFilePath($this->avatar);
    }
}
