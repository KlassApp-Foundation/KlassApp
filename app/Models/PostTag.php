<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Models\Media;
use App\Traits\Common;

class PostTag extends Model 
{
    //

    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_tags';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'tag_id','post_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public function tag()
    {
        return $this->hasMany(Tag::class);
    }

  

  
}