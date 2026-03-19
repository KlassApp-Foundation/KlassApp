<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class StudentHistory extends Model
{

	public function entity()
    {
        return $this->morphTo();
    }
    //
      use SoftDeletes;
    protected $table='student_history';
    protected $fillable=['school_id','student_id','parent_id','read_at','entity_id','entity_type','type'];

     public function parent()
    {
    	return $this->belongsTo('\App\Models\User','parent_id');
    }

    public function user()
    {
        return $this->belongsTo('\App\Models\User','student_id');
    }
    
}
