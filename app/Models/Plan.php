<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    //
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cycle' , 'name' , 'order' , 'active' , 'amount' , 'no_of_members' , 'no_of_events' , 'no_of_folders' , 'no_of_files' , 'no_of_bulletins', 'no_of_videos', 'no_of_audios', 'no_of_groups'
    ];

    protected $dates=['created_at' , 'updated_at' , 'deleted_at'];

    public function subscription()
    {
        return $this->hasMany('App\Models\Subscription','id','plan_id');
    }
}
