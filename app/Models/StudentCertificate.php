<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Common;

class StudentCertificate extends Model
{
    use SoftDeletes;
    use Common;
    protected $table = 'student_certificate';

     protected $fillable = [
        
         'school_id' , 'student_id' , 'program_name' , 'event_name' ,'certificate_for' 
    ];
}
