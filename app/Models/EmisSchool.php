<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmisSchool extends Model
{
    protected $fillable = [
        'emis_code',
        'school_name',
        'district',
        'ownership',
        'school_type',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}
