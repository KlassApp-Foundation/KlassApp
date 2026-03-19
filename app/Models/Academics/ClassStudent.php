<?php

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassStudent extends Model
{
    use HasFactory;

    protected $fillable = ["school_id", "standard_id", "student_id"];

     public function school(){
            return $this->belongsTo(School::class, "school_id");
        }
         public function standard(){
            return $this->belongsTo(Standard::class, "standard_id");
        }
       
        public function user(){
            return $this->belongsTo(User::class, "student_id");
        }
}
