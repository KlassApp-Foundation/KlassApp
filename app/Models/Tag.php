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
use App\Models\PostTag;
class Tag extends Model 
{
    //

    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tags';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'tag_name'
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

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function posttag()
    {
        return $this->belongsToMany(PostTag::class);
    }


    public function getTag($tag_name)
    {//dump($tag_name);

        $tag=Tag::where('tag_name',$tag_name)->first();

        $count=PostTag::where('tag_id',$tag->id)->count();

        return $count;
   
    }
  
}