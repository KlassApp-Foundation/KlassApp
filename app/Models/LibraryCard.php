<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryCard extends Model
{
     protected $table = 'library_card';

       protected $fillable = [
        'school_id' , 'user_id', 'library_card_no','book_limit','status','expiry_date'
    ];
}
