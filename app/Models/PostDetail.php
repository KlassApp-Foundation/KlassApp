<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PostDetail extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_details';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'user_id' , 'post_id' , 'like' , 'unlike' , 'save' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function user()
    {
    	return $this->belongsTo('\App\Models\User','user_id');
    }

    public function post()
    {
    	return $this->belongsTo('\App\Models\Post','post_id');
    }

    public function scopeByLikeCount($query,$post_id)
    {
        $count = $query->where('post_id',$post_id)->where('like',1)->count(); 
        
        return $count;
    }

    public function scopeByUnlikeCount($query,$post_id)
    {
        $count = $query->where('post_id',$post_id)->where('unlike',1)->count();  
        
        return $count;
    }
}