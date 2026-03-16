<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;
use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    //
    use SoftDeletes;
    use PresentableTrait;

    protected $presenter = "App\Presenters\UserprofilePresenter";

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'standards';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'school_id' ,  'name' , 'order' , 'status'
    ];

    public function school()
    {
        return $this->belongsTo('\App\Models\School','school_id');
    }

    public function currentPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','standard_id','id');
    }

    public function nextPromotion()
    {
        return $this->belongsTo('App\Models\Promotion','standard_id','id');
    }

    public function subject()
    {
        return $this->hasMany('\App\Models\Subject','school_id','id');
    }
    
    public function standardLink()
    {
        return $this->hasMany('\App\Models\StandardLink','standard_id','id');
    }
}