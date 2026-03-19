<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    //
     use SoftDeletes;

     protected $fillable = [
	    'school_id' ,   'standard_id' ,  'subject_id' ,' name' 
	];

    public function school()
    {
        return $this->belongsTo('\App\Models\School','school_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('\App\Models\StandardLink','standard_id');
    }

    public function subject()
    {
        return $this->belongsTo('\App\Models\Subject','subject_id');
    }

    public function questions()
    {
        return $this->hasMany('\App\Models\QuizQuestion','chapter_id','id');
    }
}
