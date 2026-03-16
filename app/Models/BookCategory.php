<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCategory extends Model
{
  protected $table = 'books_category';


    protected $fillable = [
        'school_id' , 'category'
    ];

    public function book()
    {
    	return $this->hasMany('App\Models\Book','id','category_id');
    }
}
