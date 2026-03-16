<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Church extends Model
{
    //
    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'church';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'address', 'state','city','pincode','slug','status'
    ];

    public function user()
    {
    	return $this->hasMany('App\Models\User','church_id','id')->where('usergroup_id',5);
    }

    public function admin()
    {
        return $this->hasMany('App\Models\User','church_id','id')->where('usergroup_id',3);
    }

    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','church_id','id');
    }

    public function event()
    {
        return $this->hasMany('App\Models\Event');
    }

    public function subscription()
    {
        return $this->hasMany('App\Models\Subscription','church_id','id');
    }

     public function reminder()
    {
        return $this->hasMany('App\Models\Reminder','church_id','id');
    }

    public function fulladdress()
    {
        $fulladdress =  $this->address
                        . "<br/>". $this->city
                        . "<br />" .$this->pincode 
                        . "<br /> ". $this->state
                        . "<br /> ". "India" ;

        return $fulladdress;

    }

     public function sermonlink()
    {
        return $this->hasMany('App\Models\SermonLink','church_id','id');
    }

    public function bulletin()
    {
        return $this->hasMany('App\Models\Bulletin','church_id','id');
    }

    public function group()
    {
        return $this->hasMany('App\Models\Group','church_id','id');
    }

    public function groupLink()
    {
        return $this->hasMany('App\Models\GroupLink','church_id','id');
    }

     public function sendMail()
    {
        return $this->hasMany('App\Models\SendMail','church_id','id');
    }  

    public function scopeIsActive($query,$church_id)
   {
       $query->where(function ($query) use($church_id)
            {
                $query  ->where('id',$church_id)
                        ->where('status',1); 
            });
        return $query;
   } 

   public function getActiveMembersAttribute()
   {
        return $this->hasMany('App\Models\User','church_id','id')->with('user')->where('usergroup_id',5)->whereHas('userprofile', function ($query)
            {
                $query->where('status','active');
            })->count(); 
   }

   public function getPaidMembersAttribute()
   {
        return $this->userprofile->where('membership_type','member')->count();  
   }

   public function prayerRequest()
    {
        return $this->hasMany('App\Models\PrayerRequest','church_id','id');
    }

    public function prayerResponse()
    {
        return $this->hasMany('App\Models\PrayerResponse','church_id','id');
    }

    public function help()
    {
        return $this->hasMany('App\Models\Help','church_id','id');
    }
}
