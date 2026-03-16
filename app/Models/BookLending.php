<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class BookLending extends Model
{
    use SoftDeletes;
    
    protected $table = 'books_lending';

     protected $fillable = [
        'user_id' , 'book_code_no', 'library_card_no','issue_date','return_date','issued_by','status'
    ];

    public function user()
    {
    	return $this->hasMany('\App\Models\User','id','user_id');
    }

    public function book()
    {
    	return $this->hasMany('\App\Models\Book','book_code','book_code_no');
    }

    public function userlent()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }
}
