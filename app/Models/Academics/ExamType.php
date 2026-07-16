<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamType extends Model
{
    use HasFactory;
    protected $fillable = ["name", "code", "contributes_to_report_total"];

    protected $casts = [
        'contributes_to_report_total' => 'boolean',
    ];
}
