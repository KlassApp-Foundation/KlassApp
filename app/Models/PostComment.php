<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class PostComment extends Model
{
    //
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_comments';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'user_id' , 'entity_id' , 'entity_name' , 'comments' , 'attachment_file' , 'status'
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
        return $this->belongsTo('\App\Models\Post','entity_id');
    }

    public function postComment()
    {
        return $this->belongsTo('\App\Models\PostComment','entity_id');
    }

    public function postCommentDetail()
    {
        return $this->hasMany('\App\Models\PostCommentDetail','post_comment_id','id');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachment_file);
    }

    public function getPostCommentDetailsAttribute()
    {
        $i = 0;
        $array = [];
        foreach ($this->postCommentDetail as $postCommentDetail) 
        { 
            $array[$i]['detail_id']         = $postCommentDetail->id;
            $array[$i]['user_id']           = $postCommentDetail->user_id;
            $array[$i]['user_name']         = $postCommentDetail->user->name;
            $array[$i]['user_fullname']     = ucwords($postCommentDetail->user->FullName);
            $array[$i]['user_avatar']       = $postCommentDetail->user->userprofile->AvatarPath;
            $i++;
        }

        return $array;
    }

    public function getCommentLikeCountAttribute()
    {
        if($this->postCommentDetail != null)
        {
            return $this->postCommentDetail->where('like',1)->count();
        }
        return 0;
    }

    public function getCommentUnlikeCountAttribute()
    {
        if($this->postCommentDetail != null)
        {
            return $this->postCommentDetail->where('unlike',1)->count();
        }
        return 0;
    }
}