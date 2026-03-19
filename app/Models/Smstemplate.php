<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smstemplate extends Model
{
    //
     protected $table    = 'sms_templates';
     protected $fillable = ['name','content','status'];
}