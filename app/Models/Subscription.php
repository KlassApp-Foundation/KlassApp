<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'user_id' , 'plan_id' ,'end_date', 'status', 'payment_details', 'plan_details'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $casts=['payment_details'=>'array' , 'card_details'=>'array' , 'plan_details'=>'array'];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User','user_id','id');
    }

    public function plan()
    {
    	return $this->belongsTo('App\Models\Plan','plan_id');
    }

    public function scopeDate($query,$start,$end)
    {

        $query->whereDate('created_at','>=',$start)
              ->whereDate('created_at','<=',$end);

        return $query;
    }

}