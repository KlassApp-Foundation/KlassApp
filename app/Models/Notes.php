<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    protected $table = 'notes';

     public function user()
   {
    return $this->belongsTo('App\Models\User','id');
   }

   public function event()
   {
    return $this->belongsTo('App\Models\Events','id');
   }
}
