<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StudentParentLink extends Model
{
    //
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_parent_links';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'school_id','parent_id','student_id','status'
    ];

    public function userParent()
    {
    	return $this->belongsTo('App\Models\User','parent_id')->where('usergroup_id',7);
    }

    public function userStudent()
    {
    	return $this->belongsTo('App\Models\User','student_id')->where('usergroup_id',6);
    }
}