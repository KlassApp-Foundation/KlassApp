<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{   
    use HasFactory;
    protected $table = 'books';


    protected $fillable = [
        'school_id' , 'academic_year_id', 'category_id','title','book_code','author','quantity','isbn_number','cover_image'
    ];

    public function category()
    {
    	return $this->belongsTo('\App\Models\BookCategory','category_id');
    }

    public function lending()
    {
    	return $this->belongsTo('\App\Models\BookLending','book_code');
    }
}
