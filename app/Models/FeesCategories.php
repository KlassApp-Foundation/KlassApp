<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeesCategories extends Model
{
    use HasFactory;
    protected $fillable = ["school_id", "standard_id", "section_id", "academic_term_id", "name", "amount" ];

    // for laravel to return 1.50 instead of 1.5
    protected $casts = ["amount" => "decimal:2"];

    public function school(){
        return $this->belongsTo(School::class, "school_id");
    }

     public function standard(){
        return $this->belongsTo(Standard::class, "standard_id");
    }

    public function section(){
        return $this->belongsTo(Section::class, "section_id");
    }

    public function term(){
        return $this->belongsTo(AcademicTerm::class, "academic_term_id");
    }

}
