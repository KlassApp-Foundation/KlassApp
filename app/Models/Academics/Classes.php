<?php

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Standard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;
    protected $fillable = ["name", "status", "school_id", "standard_id"];

    public function school(){
        return $this->belongsTo(School::class);
    }
    public function standard(){
        return $this->belongsTo(Standard::class);
    }
}
