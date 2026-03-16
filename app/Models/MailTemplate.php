<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    //
     protected $table='mailtemplates';
     protected $fillable=['name','subject','mail_content','status'];
}
