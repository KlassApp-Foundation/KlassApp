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

class ClassRoomPageAttachment extends Model implements HasMedia
{
    //
    use HasMediaTrait;
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'class_room_page_attachments';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'page_id' , 'attachment_file' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function classRoomPage()
    {
    	return $this->belongsTo('\App\Models\ClassRoomPage','page_id');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachment_file);
    }
}