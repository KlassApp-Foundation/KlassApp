<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentPlan extends Model
{
    use HasFactory;
    
    protected $fillable = ["school_id", "plan_id", "status", ];

    public function school(){
        return $this->belongsTo(School::class);
    }

     public function plan(){
        return $this->belongsTo(Plan::class);
    }
}
