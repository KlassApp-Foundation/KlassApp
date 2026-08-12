<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportGeneration extends Model
{
    protected $fillable = [
        'school_id',
        'standard_link_id',
        'class_name',
        'mode',
        'status',
        'file_path',
        'file_name',
        'error',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
