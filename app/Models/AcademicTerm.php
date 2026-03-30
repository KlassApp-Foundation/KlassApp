<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = ["school_id", "academic_year_id", "name", "starts_on", "ends_on", ];

    protected $casts = [
        "starts_on" => "date:Y-d-m",
        "ends_on" => "date:Y-d-m"
    ];
}
