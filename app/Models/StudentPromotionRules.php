<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPromotionRules extends Model
{
    use HasFactory;
    protected $fillable = ["school_id", "standard_id", "section_id", "min_average", "min_aggregate"];

    public function school(){
        return $this->belongsTo(School::class);
    }
      public function standard(){
        return $this->belongsTo(Standard::class);
    }
      public function section(){
        return $this->belongsTo(Section::class);
    }
}
