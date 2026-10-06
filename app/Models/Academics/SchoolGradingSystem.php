<?php

namespace App\Models\Academics;

use App\Models\School;
use App\Models\Standard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\SchoolScopedRouteBinding;

class SchoolGradingSystem extends Model
{
    use HasFactory;
    use SchoolScopedRouteBinding;

    protected $fillable = [
        'school_id',
        'standard_id',
        'grade',
        'points',
        'min_score',
        'max_score',
        'remark',
    ];

     public function school(){
        return $this->belongsTo(School::class);
    }

     public function standard(){
        return $this->belongsTo(Standard::class);
    }
}
