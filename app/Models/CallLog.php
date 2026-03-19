<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $table = 'call_log';

     protected $fillable = [
       'school_id','academic_year_id','call_type','calling_purpose','name','call_date','start_time','end_time','duration','description','entry_by'
    ];

   
  
}
