<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Spatie\MediaLibrary\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Model;
//use Spatie\MediaLibrary\Models\Media;
use App\Traits\Common;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Post extends Model implements HasMedia
{
    //
    //use HasMediaTrait;
    use InteractsWithMedia;
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'entity_id' , 'entity_name' , 'description' , 'attachment_file' , 'visibility' , 'visible_for' , 'post_created_at' , 'is_posted' , 'posted_at' , 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts=[ 'attachment_file' => 'array' ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['post_created_at','posted_at','deleted_at'];

    public function school()
    {
    	return $this->belongsTo('\App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('\App\Models\StandardLink','visible_for');
    }

    public function postDetail()
    {
        return $this->hasOne('\App\Models\PostDetail','post_id','id');
    }

    public function postComment()
    {
        return $this->hasMany('\App\Models\PostComment','entity_id','id');
    }

    public function getAttachmentPathAttribute()
    {
        $count = count($this->attachment_file);
        for($i=0 ; $i < $count ; $i++)
        {
            $attachment[$i]['original_path']    = $this->attachment_file[$i];
            $attachment[$i]['path']             = $this->getFilePath($this->attachment_file[$i]);
            $attachment[$i]['id']               = $i;
        }
        return $attachment;
    }

    public function getPostCommentsAttribute()
    {
        $i = 0;
        $array = [];
        foreach ($this->postComment as $postComment) 
        { 
            $array[$i]['comment_id']        = $postComment->id;
            $array[$i]['post_id']           = $postComment->post_id;
            $array[$i]['user_id']           = $postComment->user_id;
            $array[$i]['comments']          = $postComment->comments;
            $array[$i]['attachment_file']   = $postComment->attachment_file=='' ? null:$this->getFilePath($postComment->attachment_file);
            $array[$i]['user_name']         = $postComment->user->name;
            $array[$i]['user_fullname']     = ucwords($postComment->user->FullName);
            $array[$i]['user_avatar']       = $postComment->user->userprofile->AvatarPath;
            $array[$i]['commented_at']      = $postComment->updated_at->diffForHumans();
            $array[$i]['comment_details']   = $postComment->PostCommentDetails;
            $array[$i]['like_count']        = $postComment->CommentLikeCount;
            $array[$i]['unlike_count']      = $postComment->CommentUnlikeCount;
            $postCommentDetail = PostCommentDetail::where('post_comment_id',$postComment->id)->first();
            if($postCommentDetail != null)
            {
                $array[$i]['like']              = $postCommentDetail->like;
                $array[$i]['unlike']            = $postCommentDetail->unlike;
            }
            $i++;
        }

        return $array;
    }

    public function tag()
    {
        return $this->belongsToMany(Tag::class,'post_tags');
    }
}