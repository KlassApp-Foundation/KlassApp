<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parent_profiles';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
      'school_id' , 'user_id' , 'qualification_id' , 'profession' , 'sub_occupation'  , 'designation' , 'organization_name' , 'official_address' , 'relation' , 'annual_income'
    ];

    public function school()
    {
      return $this->belongsTo('App\Models\School','school_id');
    }

    public function user()
    {
      return $this->belongsTo('App\Models\User','user_id');
    }

    public function qualification()
    {
        return $this->belongsTo('\App\Models\Qualification','qualification_id');
    }
}
