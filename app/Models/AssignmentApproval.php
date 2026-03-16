<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AssignmentApproval extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'assignment_approvals';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'assignment_id' , 'comments' , 'status' , 'approved_by' , 'approved_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['approved_at' , 'deleted_at'];

    public function approvedBy()
    {
    	return $this->belongsTo('\App\Models\User','approved_by');
    }

    public function assignment()
    {
    	return $this->belongsTo('\App\Models\Assignment','assignment_id');
    }
}