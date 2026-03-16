<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    //
    use SoftDeletes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'school_id' , 'name' , 'value' , 'status'
    ];

    public function school()
    {
    	return $this->belongsTo('\App\Models\School','school_id');
    }

    public function currentPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','section_id','id');
    }

    public function nextPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','section_id','id');
    }

    public function subject()
    {
        return $this->hasMany('\App\Models\Subject','section_id','id');
    }

    public function standardLink()
    {
        return $this->hasMany('App\Models\StandardLink','section_id','id');
    }
}
